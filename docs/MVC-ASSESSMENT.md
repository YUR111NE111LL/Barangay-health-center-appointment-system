# MVC Assessment – Barangay Health Center Appointment System

## Summary

**Yes, the system follows MVC correctly overall.** It uses Laravel’s standard structure: **Models** for data and domain logic, **Views** for presentation, and **Controllers** to coordinate requests, models, and views. A few spots put logic or queries in views; those are noted below with suggested improvements.

---

## What the project does well

### 1. **Clear separation of roles**

| Layer   | Location              | Responsibility                                      |
|--------|------------------------|-----------------------------------------------------|
| **Model**   | `app/Models/`          | Entities, relationships, scopes, domain helpers (e.g. `Tenant::canExceedAppointmentLimit()`, `User::hasTenantPermission()`) |
| **View**    | `resources/views/`     | Blade templates by feature (`backend/`, `frontend/`, `auth/`, `superadmin/`) |
| **Controller** | `app/Http/Controllers/` | Request handling, validation, authorization, calling models and returning views |

### 2. **Routes → Controllers → Views**

- Routes in `routes/web.php` map to controller actions; no business logic in routes (except minimal redirect logic).
- Controllers load models, run validation/authorization, and `return view(..., compact(...))`.
- Views receive data and render HTML; they don’t decide *which* data to load.

### 3. **Models**

- Eloquent models with `$fillable`, `casts`, and relationships (`belongsTo`, `hasMany`).
- Domain logic lives on models (e.g. `Tenant::getPrimaryColor()`, `User::hasTenantPermission()`).
- Scopes and traits (`BelongsToTenant`, `TenantScope`) keep tenant logic in one place.

### 4. **Extra layers (beyond “bare” MVC)**

- **Services:** `App\Services\TenantRbacSeeder` for RBAC seeding (no UI, no HTTP).
- **Events / Listeners:** `AppointmentSaved`, `TenantRbacUpdated`, `TenantCustomizationUpdated`; listeners for notifications.
- **Middleware:** Auth, tenant context, role/permission checks.

This matches common Laravel practice: MVC plus services, events, and middleware.

---

## Where it could be improved

### 1. **Queries and logic in views (main deviation)**

In strict MVC, views should not run queries or contain business logic. Currently:

- **`resources/views/backend/layouts/app.blade.php`**  
  Uses `@php` to run a query for the “pending approvals” count. That belongs in a controller or a view composer.

- **`resources/views/superadmin/layouts/app.blade.php`**  
  Same idea: pending count is queried inside the layout.

- **`resources/views/backend/appointments/create.blade.php`**  
  Runs `auth()->user()->tenant->users()->where('role', 'Resident')->...->get()` in the template. The list of residents should be passed from the controller.

**Fix:** Use **view composers** (or equivalent) to inject layout data (e.g. pending counts), and have **controllers** pass page-specific data (e.g. residents for the create form). The codebase has been updated in this direction: controller passes `$residents`, and view composers inject pending counts so layouts stay query-free.

### 2. **No dedicated Form Request classes**

Validation is done inside controllers with `$request->validate(...)`. This is valid in Laravel, but for complex or reused rules, **Form Request** classes (`app/Http/Requests/`) would:

- Keep controllers thinner.
- Reuse validation and authorization in one place.

This is an optional improvement, not an MVC “violation.”

### 3. **Direct `DB::table()` in some controllers**

`TenantRbacController` and `RolePermissionsController` use `DB::table('tenant_role_permissions')` and `Schema::hasTable()`. That’s acceptable for a simple permission table without an Eloquent model, but introducing a **TenantRolePermission** model (or similar) would align better with “everything goes through models” and make the code easier to test and reuse.

---

## Conclusion

The system **does follow MVC correctly**: models own data and domain logic, views handle presentation, and controllers orchestrate and delegate. The main improvement is to **avoid queries and business logic in views** by using view composers and passing all data from controllers; the suggested changes do that without changing the overall architecture.
