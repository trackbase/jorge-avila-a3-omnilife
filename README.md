# Tienda · Jorge Luis Ávila Gaxiola

Prueba técnica para aKúbica (Full Stack PHP/Laravel): tienda en línea con panel de administración, catálogo con filtros, carrito, checkout con pago simulado y reportes.

> Proyecto demostrativo para evaluación técnica — no es un sitio oficial de Omnilife. Los productos, precios e imágenes son ficticios.

**Stack:** Laravel 12 · Livewire 3 (con su Alpine.js) · Bootstrap 5.3 + Bootstrap Icons (Vite + Sass) · Chart.js 4 · MySQL 8 · Redis (caché y sesiones, cliente `predis`) · PHPUnit.

## Funcionalidad
- **Panel `/admin`** (protegido con `auth`):
  - Inicio de sesión con límite de 5 intentos por minuto.
  - CRUD de productos: búsqueda, orden y paginación en tiempo real, validación en vivo, imagen, activar/inactivar y eliminación lógica con restauración.
  - Precio MXN calculado con el tipo de cambio en caché.
  - Pedidos con filtro por rango de fechas y detalle.
  - Gráfica de ganancias por mes o semana y Top 3 de productos más vendidos.
- **Tienda `/`:**
  - Catálogo de productos activos y vigentes, con filtros sin recargar (categoría, precio, disponibilidad, búsqueda y orden).
  - Detalle `/producto/{sku}`.
  - Carrito en sesión que respeta el stock.
  - Checkout `/checkout` con subtotal, IVA 16 % y total en MXN y USD, correo obligatorio y pago simulado.
- **Webservice:** tipo de cambio USD/MXN de Banxico (respaldo: Frankfurter), cacheado en Redis.

## Requisitos
- PHP 8.2 o superior con `pdo_mysql` (desarrollado con PHP 8.3), Composer 2.
- Node 20 o superior y npm.
- MySQL 8 y Redis 5 o superior. No hace falta la extensión `redis` de PHP: se usa `predis/predis`.
- Desarrollado con [Laragon](https://laragon.org) en Windows, que crea el virtual host `tienda.test` a partir del nombre de la carpeta.

## Instalación
```bash
# 1. Clonar en una carpeta llamada "tienda" (Laragon la publica como http://tienda.test)
git clone https://github.com/trackbase/jorge-avila-a3-omnilife.git tienda
cd tienda

# 2. Dependencias y configuración
composer install
cp .env.example .env
php artisan key:generate

# 3. Bases de datos (la de pruebas es aparte)
mysql -u root -e "CREATE DATABASE tienda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE tienda_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Tablas, datos de demostración e imágenes
php artisan migrate --seed
php artisan storage:link

# 5. Assets
npm install
npm run build
```
En Laragon: **Menu → Apache → Reload** (o *Stop/Start All*) para que aparezca `tienda.test`. Sin Laragon, apunta un virtual host a la carpeta `public/` o usa `php artisan serve` y ajusta `APP_URL`.

> El pago simulado es una petición HTTP de la app a su propia API (`route('api.pagos.simular')`, en el mismo host). Con `php artisan serve` usa varios workers (`PHP_CLI_SERVER_WORKERS=4`, ya está en `.env.example`) para que esa petición no se quede esperando.

Los datos de demostración incluyen:
- El usuario administrador.
- 5 categorías.
- 30 productos con imágenes SVG generadas localmente (algunos inactivos, vencidos o sin stock).
- ~130 pedidos repartidos en los últimos 6 meses, para que la gráfica y el Top 3 tengan datos.

## Credenciales de prueba
| Qué | Valor |
|---|---|
| Admin (`http://tienda.test/admin/login`) | `admin@tienda.test` / `Admin123!` |
| Tarjeta aprobada | `4242 4242 4242 4242` |
| Tarjeta rechazada | `4000 0000 0000 0002` |
| Cualquier otra tarjeta | "Tarjeta inválida" |
| Vencimiento y CVV | Cualquier fecha futura `MM/AA` y 3 dígitos |

## Variables de entorno
| Variable | Valor en `.env.example` | Para qué |
|---|---|---|
| `APP_NAME` | `"Jorge Avila A3 Omnilife"` | Nombre de la app; también genera los prefijos de las llaves en Redis |
| `APP_URL` | `http://tienda.test` | URL base; las imágenes de productos se sirven en `APP_URL/storage` (debe coincidir con el host) |
| `APP_LOCALE` / `APP_FAKER_LOCALE` | `es` / `es_MX` | Mensajes en español y datos de prueba mexicanos |
| `DB_CONNECTION`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `mysql`, `tienda`, `root`, vacío | Base de datos |
| `SESSION_DRIVER` | `redis` | Sesión (y carrito) en Redis |
| `CACHE_STORE` | `redis` | Caché (tipo de cambio y límite de intentos) en Redis |
| `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT` | `predis`, `127.0.0.1`, `6379` | Conexión a Redis |
| `IVA_TASA` | `0.16` | Tasa de IVA del checkout (`config/tienda.php`) |
| `TIPO_CAMBIO_TTL` | `3600` | Segundos que el tipo de cambio vive en la caché |
| `BANXICO_TOKEN` | vacío | Token de la API SIE de Banxico (opcional, ver abajo) |

Si cambias el `.env` con la configuración cacheada, corre `php artisan config:clear`.

## Webservice: tipo de cambio USD/MXN
`App\Services\TipoCambioService` obtiene el tipo de cambio y lo guarda en Redis. El precio en USD es la fuente; el formulario de productos calcula el precio en MXN con ese valor (botón **Recalcular**) y muestra la fuente y la fecha.

1. **Banxico SIE** (principal). Serie `SF43718`, tipo de cambio FIX:
   ```
   GET https://www.banxico.org.mx/SieAPIRest/service/v1/series/SF43718/datos/oportuno
   Header: Bmx-Token: <tu token>
   ```
2. **Frankfurter** (respaldo, sin llave). Datos del Banco Central Europeo:
   ```
   GET https://api.frankfurter.dev/v1/latest?base=USD&symbols=MXN
   ```
3. **Último valor conocido**: si las dos APIs fallan, se usa el último valor guardado.

En Redis se guardan dos llaves:
- `tipo_cambio:usd_mxn`: dura `TIPO_CAMBIO_TTL` segundos y evita llamadas innecesarias a las APIs.
- `tipo_cambio:ultimo`: no expira; es el último valor conocido.

Cada API se consulta con `Http::timeout(5)->retry(2, 200)`: hasta 2 intentos de 5 s como máximo, con 200 ms entre ellos.

### Cómo obtener y configurar el token de Banxico
1. Solicita el token gratis en https://www.banxico.org.mx/SieAPIRest/service/v1/token (pide un correo; el token tiene 64 caracteres).
2. Pégalo en `.env`: `BANXICO_TOKEN=tu_token`.
3. Corre `php artisan config:clear`.

Sin token la tienda también funciona: va directo a Frankfurter. Con un token inválido, Banxico responde con error y también se usa Frankfurter.

### Verlo en Redis
La caché usa la base 1 de Redis y las llaves llevan los prefijos de `APP_NAME`:
```bash
redis-cli -n 1 KEYS "*tipo_cambio*"
redis-cli -n 1 TTL "<llave completa>"   # ≈ TIPO_CAMBIO_TTL; la llave "ultimo" da -1 (sin expiración)
```

## Pago simulado
Mock REST propio que actúa como banco: `POST /api/pagos/simular` con `numero_tarjeta`, `monto` y `referencia`.

| Respuesta | Caso |
|---|---|
| `200` aprobado | Tarjeta `4242…` |
| `402` rechazado | Tarjeta `4000…0002` |
| `422` | Tarjeta inválida o datos faltantes |

- Límite de 20 peticiones por minuto.
- La tienda lo consume con `Http::` a través de la interfaz `PasarelaPago`. Cambiar a una pasarela real es otra implementación de esa interfaz.
- Del pago solo se guardan la referencia y los últimos 4 dígitos.

## Pruebas
```bash
php artisan test
```
- Usan la base MySQL `tienda_test` (no SQLite), para probar los reportes y `lockForUpdate` como en producción.
- La caché y la sesión usan el driver `array`.
- `Http::preventStrayRequests()` impide llamadas reales a internet, así que no dependen de las APIs.

## Estructura
| Ruta | Contenido |
|---|---|
| `app/Livewire/Admin/` | Login, Dashboard, `Productos/` (Index, Formulario, Detalle) y `Pedidos/Index` |
| `app/Livewire/Tienda/` | Catalogo, ProductoDetalle, Carrito, CarritoIcono y Checkout |
| `app/Livewire/Forms/ProductoForm.php` | Form Object con las reglas del producto |
| `app/Services/` | `TipoCambioService`, `CarritoService`, `PedidoService`, `ReporteService` y `Pagos/` (PasarelaPago, PagoSimulado) |
| `app/Http/Controllers/Api/PagoSimuladoController.php` | Banco simulado |
| `app/Enums/EstatusPedido.php` · `app/Rules/SinAcentosNiEspeciales.php` | Estatus del pedido y regla del nombre |
| `config/tienda.php` | IVA y TTL del tipo de cambio |
| `resources/views/components/layouts/` | Layouts `app` (tienda) y `admin` |
| `resources/js/admin.js` | Gráfica de Chart.js (solo se carga en el panel) |

Las decisiones técnicas y sus razones están en [DECISIONES.md](DECISIONES.md).
