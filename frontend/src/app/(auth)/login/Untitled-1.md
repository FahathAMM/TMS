Build a **Simple Mobile Shop POS System** with the following tech stack and requirements:
Build a Simple Mobile Shop POS System with Laravel 13 Backend API and Next.js Frontend as two completely separate applications.

### Tech Stack

* **Frontend:** Next.js (latest stable version) using JavaScript (not TypeScript)
* **Backend:** Laravel 13 REST API
* **Database:** MySQL
* **Authentication:** Laravel Sanctum
* **UI Components:** shadcn/ui + reui
* **Styling:** Tailwind CSS

### Development Standards

* Follow clean architecture and modular folder structure.
* Use reusable and shared components wherever possible.
* Create reusable form components:

  * Text Input
  * Select Dropdown
  * Textarea
  * Date Picker
  * Number Input
  * File Upload
  * Validation Error Component
* Implement proper client-side and server-side validation.
* Use API services and custom hooks to avoid duplicate code.
* Follow consistent coding standards and naming conventions.

### Authentication & Authorization

* Login page
* Logout functionality
* Protected routes
* User session management using Laravel Sanctum
* Role-based access support (Admin, Staff)

### POS Features

#### Dashboard

* Today's sales summary
* Total sales
* Total orders
* Low stock products
* Recent transactions

#### Product Management

* Product CRUD
* Product code/SKU
* Product name
* Category
* Brand
* Cost price
* Selling price
* Stock quantity
* Product image
* Active/Inactive status

#### Category Management

* Category CRUD

#### Customer Management

* Customer CRUD
* Name
* Mobile number
* Address

#### POS Sales Screen

* Product search
* Barcode/SKU search
* Add products to cart
* Quantity update
* Discount support
* Tax support
* Order notes
* Customer selection
* Cash payment
* Print receipt

#### Sales Management

* Sales history
* View sale details
* Print invoice
* Cancel sale

#### Inventory

* Stock adjustments
* Stock history
* Low stock alerts

### Backend Requirements

* Laravel 13 API only
* Resource Controllers
* Form Request Validation
* API Resources
* Service Layer Pattern
* Repository Pattern where needed
* Database Seeders
* RESTful API structure

### Frontend Requirements

* Next.js App Router
* Reusable layouts
* Reusable DataTable component
* Reusable Modal component
* Reusable Confirm Dialog component
* Loading states
* Error handling
* Pagination
* Search and filters

### Deliverables

1. Complete project folder structure.
2. Database schema and migrations.
3. Laravel API endpoints.
4. Next.js page structure.
5. Reusable component structure.
6. Step-by-step implementation plan.
7. Generate production-ready code module by module.
8. Do not skip any files or configurations.
