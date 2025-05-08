# Portal Pinta360 (S360) – Estructura de Proyecto  
_(Actualizado a mayo 2025; incluye módulo de Tickets completo, panel Operativas y panel Admin)_

---
## Raíz del Proyecto `/p360`
```
/p360
│
├── assets/
│   ├── css/            ← `style.css` — hoja de estilo principal
│   ├── images/         ← Logos, íconos, recursos estáticos
│   └── js/             ← Scripts vanilla para interacciones menores
│
├── auth/
│   ├── login.php       ← Login + creación de variables de sesión
│   ├── logout.php      ← Cierra sesión ( `session_destroy()` )
│   └── hash.php        ← Helpers de hash / verificación de contraseña
│
├── dashboard/
│   ├── admin.php               ← Dashboard rol **admin**
│   ├── compras.php             ← Dashboard rol **compras** (cliente)
│   ├── operativas.php          ← Dashboard rol **operativas** (soporte)
│   │
│   ├── ✦ Tickets (Operativas)
│   │   ├── create_ticket.php   ← Form crear ticket (adjuntos, prioridad, teléfono)
│   │   ├── ticket_history.php  ← Historial de tickets del operativo
│   │   └── ticket_detail.php   ← Detalle cliente‑operativo + comentarios
│   │
│   ├── ✦ Tickets (Admin)
│   │   ├── view_tickets_admin.php   ← Filtro + listado global de tickets
│   │   └── ticket_detail_admin.php  ← Cambiar estatus / prioridad + comentar
│   │
│   ├── Pedidos
│   │   ├── create_order.php       ← Alta de pedido (cliente)
│   │   ├── manage_orders.php      ← Vista tabla pedidos (admin)
│   │   ├── order_detail.php       ← Detalle pedido (cliente)
│   │   └── order_detail_admin.php ← Detalle pedido (admin)
│   │
│   ├── Gestión Maestros (Admin)
│   │   ├── manage_companies.php        ← Empresas
│   │   ├── manage_company_users.php    ← Usuarios por empresa
│   │   ├── manage_price_lists.php      ← Listas de precio
│   │   └── manage_products.php         ← Catálogo productos
│   └── error_log              ← Log PHP de debug (solo desarrollo)
│
├── includes/
│   ├── header.php        ← Navbar + logo + menú dinámico por rol
│   ├── footer.php        ← Footer copyright
│   └── functions.php     ← Helpers generales (escape, mail, etc.)
│
├── lib/
│   └── PHPMailer/        ← Biblioteca para notificaciones correo
│
├── uploads/
│   └── tickets/{ticket_id}/  ← Carpeta con adjuntos de cada ticket
│
├── config.php            ← Credenciales DB, `$pdo`, constantes
├── index.php             ← Redirige a `/auth/login.php`
└── README.md             ← ESTE documento
```
---
## Base de Datos `ffteqbal_p360_db`
| Tabla | Propósito / columnas relevantes |
|-------|----------------------------------|
| **companies** | Empresas (id, name, rfc, …) |
| **users** | Usuarios → `id, company_id, role (admin / compras / operativas), name, email, password_hash` |
| **products** | Catálogo producto (id, name, unit, base_price) |
| **price_lists** | Precios específicos por empresa / producto |
| **orders** | Pedidos (company_id, user_id, _status_op_, _status_fin_, subtotal, iva, total, created_at) |
| **order_items** | Detalle por pedido |
| **order_status_history** | Bitácora de estados de pedido |
| **tickets** | **➜** Soporte (company_id, user_id, subject, description, **phone**, priority, status, created_at) |
| **ticket_attachments** | (id, ticket_id, file_path, uploaded_at) |
| **tickets_comments** | (id, ticket_id, **author**, comment, created) <!-- nota: tabla plural --> |

> **Cambio clave**: se añadió la columna **`phone`** a `tickets` y se creó la tabla **`tickets_comments`** (plural), donde la columna `author` almacena ahora `users.name`.

---
## Flujo de Tickets
1. **Operativas** ingresa → `create_ticket.php` y registra ticket con prioridad, teléfono y adjuntos.
2. Tras guardar, se muestra mensaje de confirmación y link a **Historial** (`ticket_history.php`).
3. **Operativas** puede consultar / comentar cada ticket en `ticket_detail.php`.
4. **Admin** gestiona todos los tickets desde `view_tickets_admin.php` (filtra por estado / prioridad).
5. En `ticket_detail_admin.php` el admin:
   - Cambia **Estatus** (`Abierto / En Proceso / Cerrado`).
   - Cambia **Prioridad** (`Alta / Media / Baja`).
   - Responde con comentario (se inserta en `tickets_comments`).
6. Todas las acciones registran autor y fecha en la bitácora de comentarios.

---
## Rutas & `include`
* Cada archivo bajo **dashboard/** hace:
  ```php
  require_once __DIR__ . '/../config.php';
  require __DIR__ . '/../includes/header.php';
  … código…
  require __DIR__ . '/../includes/footer.php';
  ```
* Las URLs en `<a>` son relativas a la carpeta `/p360/dashboard/`.

---
## Convenciones de Rol
| Rol (`users.role`) | Dashboard | Permisos Tickets |
|--------------------|-----------|------------------|
| **admin**          | `admin.php` | Ver todos, cambiar estatus / prioridad, comentar |
| **operativas**     | `operativas.php` | Crear, ver propios, comentar |
| **compras**        | `compras.php` | Solo lee sus tickets y pedidos |


