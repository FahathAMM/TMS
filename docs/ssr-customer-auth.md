# SSR Customer Auth — How It Works

**Date:** 2026-06-14  
**Author:** Claude (Sonnet 4.6)  
**Project:** Mobile Shop POS — Next.js Storefront

---

## What is SSR?

SSR stands for **Server-Side Rendering**. It means the server fetches data and builds the full HTML page *before* sending it to the browser.

### CSR vs SSR (simple comparison)

**CSR — Client-Side Rendering (old way):**
```
Browser → gets empty HTML → downloads JS → JS runs → JS fetches data → page shows
```

**SSR — Server-Side Rendering (our way):**
```
Browser → server fetches data + builds HTML → browser gets full page → page shows immediately
```

The user sees real content on the very first paint because the data is already inside the HTML.

---

## Why We Use SSR for Auth

### The old problem (Zustand + localStorage)

Before the refactor, customer auth was stored in `localStorage` via a Zustand store.

**Problem:** `localStorage` is browser-only. The server has no access to it.

So every page load looked like this:

```
[0ms]   Browser gets HTML → shows "Sign In" (server doesn't know you're logged in)
[200ms] JavaScript loads
[210ms] Zustand reads localStorage → finds your token
[220ms] Shows your name  ← USER SEES A FLICKER
```

### The new way (SSR + cookie)

Cookies travel with every HTTP request automatically. The server can read them before sending any HTML.

```
[0ms]   Server reads your cookie → calls Laravel → gets your name
[50ms]  Server builds HTML with your name already in it
[50ms]  Browser gets HTML → shows your name immediately  ← NO FLICKER
[250ms] JavaScript loads → nothing changes (already correct)
```

---

## The Files

| File | Role |
|------|------|
| `src/lib/shopCookie.js` | Read / write / delete the `shop_token` cookie |
| `src/context/CustomerAuthContext.jsx` | React context + `useCustomerAuth` hook |
| `src/app/(store)/layout.jsx` | Server Component — fetches customer before rendering |
| `src/lib/storeAxios.js` | Axios instance — reads cookie for API calls |

---

## Step-by-Step Flow

### Step 1 — Login (happens only once, client-side)

```js
// CustomerAuthContext.jsx
async function login(credentials) {
  const { data } = await storeApi.post("/auth/login", credentials);
  setShopToken(data.token);   // saves token to cookie
  setCustomer(data.customer); // updates React state immediately
}
```

```js
// shopCookie.js
export function setShopToken(token) {
  const expires = new Date(Date.now() + 7 * 864e5).toUTCString();
  document.cookie = `shop_token=${token}; path=/; expires=${expires}; SameSite=Lax`;
}
```

The `shop_token` cookie is now stored in the browser for 7 days.

---

### Step 2 — Every Page Visit (SSR on server)

When the user navigates to any storefront page, Next.js runs the layout on the **server**:

```js
// (store)/layout.jsx — runs on the SERVER
async function getCustomer() {
  // Read the cookie from the incoming browser request
  const token = (await cookies()).get("shop_token")?.value;

  if (!token) return null; // not logged in

  // Server calls Laravel directly (no browser involved)
  const res = await fetch("http://127.0.0.1:8000/api/storefront/auth/me", {
    headers: { Authorization: `Bearer ${token}` },
    cache: "no-store",
  });

  if (!res.ok) return null; // token expired or invalid
  const json = await res.json();
  return json.data; // { id: 1, name: "Fahath", email: "..." }
}
```

Settings and customer are fetched **at the same time** to save time:

```js
const [initialSettings, initialCustomer] = await Promise.all([
  getSettings(),  // store logo, name, announcement, etc.
  getCustomer(),  // logged-in customer or null
]);
```

---

### Step 3 — Pass Data into React Context

```js
// layout.jsx
return (
  <CustomerAuthProvider initialCustomer={initialCustomer}>
    <StoreSettingsProvider initialSettings={initialSettings}>
      <StoreHeader />
      <main>{children}</main>
      <StoreFooter />
    </StoreSettingsProvider>
  </CustomerAuthProvider>
);
```

```js
// CustomerAuthContext.jsx
export function CustomerAuthProvider({ children, initialCustomer = null }) {
  // useState starts with the real customer — no loading state needed
  const [customer, setCustomer] = useState(initialCustomer);

  return (
    <CustomerAuthContext.Provider
      value={{ customer, isAuthenticated: !!customer, login, register, logout }}
    >
      {children}
    </CustomerAuthContext.Provider>
  );
}
```

Because `useState(initialCustomer)` already has real data, the component renders correctly on the first paint with no loading state.

---

### Step 4 — Browser Receives Complete HTML

The HTML the browser gets already contains:

```html
<!-- If logged in -->
<span>Fahath</span>

<!-- If not logged in -->
<a href="/shop/auth/login">Sign In</a>
```

No JavaScript has run yet. The page is already correct.

---

### Step 5 — Client-Side After Hydration

After JavaScript loads, React "hydrates" the page (attaches event listeners). The context already has the correct customer data so nothing visually changes.

When the user logs out:

```js
async function logout() {
  try { await storeApi.post("/auth/logout"); } catch {}
  removeShopToken(); // deletes the cookie
  setCustomer(null); // clears React state
  router.push("/shop/auth/login");
}
```

---

## Why a Cookie Instead of localStorage?

| | `localStorage` | Cookie |
|--|--|--|
| Server can read it | No | Yes |
| Sent with every request | No | Yes (automatically) |
| SSR compatible | No | Yes |
| JavaScript can read it | Yes | Yes (if not HttpOnly) |
| Expires automatically | No | Yes (set expiry) |

We use a **readable cookie** (not HttpOnly) so that:
- The server can read it for SSR (`cookies()` from `next/headers`)
- The client (storeAxios) can also read it to add the Bearer token to API requests

---

## How All Components Use Auth

Every component that needs to know if the user is logged in just calls one hook:

```js
import { useCustomerAuth } from "@/context/CustomerAuthContext";

// Inside any component:
const { customer, isAuthenticated, login, register, logout } = useCustomerAuth();
```

No Zustand. No `_hasHydrated`. No localStorage. Just a simple React context that starts with SSR data.

---

## Summary

```
LOGIN
  ↓
Token saved in cookie (7 days)

EVERY PAGE LOAD
  ↓
Next.js server reads cookie
  ↓
Server calls Laravel /auth/me
  ↓
Gets customer object { name, email, ... }
  ↓
Passes to CustomerAuthProvider as initialCustomer
  ↓
useState(initialCustomer) — starts with real data
  ↓
HTML sent to browser with correct auth state
  ↓
User sees their name immediately — no flicker
```
