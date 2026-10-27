# Decisiones técnicas

Cada decisión con su razón. Las marcadas como **supuesto** cubren huecos del enunciado.

## Stack y front
| # | Decisión | Por qué |
|---|---|---|
| D1 | Bootstrap 5.3 (Vite + Sass) en lugar de Tailwind | Lo domino, la vacante no exige Tailwind y Livewire no depende del CSS. La paginación de Livewire usa el tema `bootstrap`. Se quitaron `axios` y `bootstrap.js` del skeleton: Livewire ya hace las peticiones. |
| D2 | Identidad visual inspirada en Omnilife (`#66308C`, `#1F1A1F`, `#C8A5E0`, `#F7F3FA`, Montserrat) | Diseño coherente con el cliente final, sin recrear el logo ni usar imágenes o precios reales. El aviso "no es un sitio oficial" va en el pie y en el login. |
| D3 | Interacción con Alpine (el que trae Livewire): navbar, toasts, modales y +/− del carrito | Alpine sobrevive a los re-renderizados de Livewire; el JS de Bootstrap manipularía el DOM por fuera. Instalar Alpine aparte duplicaría instancias. Livewire es la fuente de verdad y Alpine solo aporta UX. |
| — | Componentes Livewire de página completa con `#[Layout]` y `#[Title]` | La clase es controlador y estado a la vez; no hacen falta controladores para las pantallas. |
| — | Chart.js como entrada de Vite aparte (`resources/js/admin.js`) | Solo la carga el panel; la tienda no descarga ~200 KB de gráficas. |

## Datos
| # | Decisión | Por qué |
|---|---|---|
| A1 | Tabla `categorias` (id, nombre, slug) y `productos.categoria_id` con FK e índice | El filtro por categoría lo exige y la tabla original no la trae. |
| A7 | Dinero en `DECIMAL(12,2)`, nunca `float`; el pedido guarda totales en MXN más `total_usd` y `tipo_cambio` (`DECIMAL(10,4)`) | Evita errores de redondeo binario y deja el pedido auditable aunque cambie el tipo de cambio. |
| A8 | Enum `EstatusPedido`: `pendiente`, `pagado`, `rechazado`, `cancelado` | Pre-pedido `pendiente` → `pagado` (descuenta stock) o `rechazado`. `cancelado` cubre el caso en que el banco aprueba pero otro cliente se llevó el stock mientras tanto. |
| A9 | Folio `PED-AAAA-000123` con índice único, generado dentro de la transacción | Legible para el cliente y sin duplicados. |
| A10 | `pedido_detalle` con `sku` y `nombre` como fotografía histórica e índice único (`pedido_id`, `producto_id`) | El pedido no cambia si después se edita o elimina el producto. |
| A11 | `PedidoDetalle` con `protected $table = 'pedido_detalle'` | El pluralizador de Laravel es en inglés. |
| — | Índices `productos(activo, fecha_vigencia)` y `pedidos(estatus, fecha)` | Cubren el `WHERE` del catálogo y de los reportes. |

## Reglas de negocio
| # | Decisión | Por qué |
|---|---|---|
| A2 | **Supuesto:** ganancia = ventas netas sin IVA (subtotal) de pedidos `pagado`, agrupadas por mes o semana ISO | El modelo no tiene costo; el IVA no es ingreso. Alternativa documentada: agregar `costo_usd` y restarlo. |
| A3 | `precio_usd` es la fuente; el formulario calcula `precio_mxn` con el tipo de cambio cacheado (botón "Recalcular") y permite ajustarlo | El catálogo filtra y ordena por MXN sin llamar a la API en cada visita, y el admin ve el tipo de cambio, su fuente y su fecha. |
| A4 | **Supuesto:** vigente = `fecha_vigencia >= hoy` (zona `America/Mexico_City`); scope `Producto::visibles()` = activo + vigente | Los eliminados ya los filtra `SoftDeletes`. Se compara con la fecha sin `DATE()` para que MySQL use el índice. |
| A5 | SKU único global, incluidos los eliminados | Un SKU eliminado no se reutiliza: se restaura. (EXTRA) El listado tiene "Ver eliminados" con botón **Restaurar**. |
| A6 | **Supuesto:** nombre con la regla propia `SinAcentosNiEspeciales` (`\A[A-Za-z0-9 \-]+\z`) | Letras sin acento, dígitos, espacio y guion. La **ñ queda fuera**. `\A…\z` en vez de `^…$` porque `$` acepta un salto de línea final. |
| — | IVA en `config/tienda.php` (`IVA_TASA`) | Cambiar la tasa no toca código. |
| — | Precios con máximo 2 decimales (`decimal:0,2`) y `trim()` de SKU y nombre | Livewire no pasa por el middleware `TrimStrings`, y `0.004` pasaría `> 0` pero se guardaría como `0.00`. |

## Autenticación (D6)
- Login Livewire propio: `Auth::attempt`, `session()->regenerate()` y `RateLimiter` de 5 intentos por minuto.
- La llave del límite es el correo normalizado (`Str::transliterate` + minúsculas) y la IP, para que variantes Unicode del correo no abran otro contador.
- Rutas `/admin` con el middleware `auth`; la ruta del login se llama `login` porque es a donde redirige `auth`.
- Google con Socialite queda fuera (opcional en la prueba).

## Tipo de cambio y Redis (D4, R15, R25)
- **Banxico SIE**, serie FIX `SF43718`, con header `Bmx-Token`. Respaldo: **Frankfurter** (sin llave). Sin token se va directo a Frankfurter.
- `Cache::remember('tipo_cambio:usd_mxn', TIPO_CAMBIO_TTL)` evita llamadas innecesarias.
- `tipo_cambio:ultimo`, sin expiración, es el último recurso si ambas APIs fallan.
- El valor de respaldo también se cachea el TTL completo, a propósito: con las APIs caídas, cada visita esperaría los timeouts.
- `Http::timeout(5)->retry(2, 200)` para leer el tipo de cambio (GET idempotente).
- Sesión y caché en Redis (`SESSION_DRIVER=redis`, `CACHE_STORE=redis`) con `predis`, porque la extensión `redis` de PHP no está instalada en Laragon. La sesión vive en la base 0 y la caché en la 1.

## Carrito y checkout (R21–R24, R27)
- **Carrito en sesión** a través de `CarritoService`. Guarda solo `[producto_id => cantidad]`; precios, stock y visibilidad se leen de la BD en cada render, así que el carrito nunca muestra un precio viejo y se recorta si baja el stock.
- **Pago (D5):** mock REST propio `POST /api/pagos/simular`, consumido con `Http::` desde `PagoSimulado`, que implementa la interfaz `PasarelaPago`. Cambiar a Stripe u Openpay = otra clase + una línea en `AppServiceProvider`.
- **Sin retry en el cobro**: reintentar un POST de cobro puede duplicar el cargo.
- Al mock se le aplica `throttle:20,1` para impedir enumerar tarjetas.
- **Dos transacciones cortas con `lockForUpdate` y el cobro fuera de ellas** (`PedidoService`):
  1. TX1: bloquea los productos, valida el stock que vio el cliente y crea el pre-pedido `pendiente`.
  2. Cobro, sin filas bloqueadas mientras responde el banco.
  3. TX2: vuelve a bloquear, revalida y descuenta el stock y marca `pagado`, o `cancelado` si ya no alcanza.

  Cobrar dentro de la transacción dejaría las filas bloqueadas hasta el timeout del banco.
- **Tarjeta:** solo se guardan los últimos 4 dígitos y la referencia. Tras pagar, el componente borra el número y el CVV de su estado, y `#[Locked] $pedidoId` impide ver pedidos ajenos. En producción la tarjeta se tokenizaría en el navegador con el SDK de la pasarela.
- **Tipo de cambio del pedido:** se pide antes de la TX1 para no hacer HTTP con filas bloqueadas. Si no hay ninguno disponible, se usa el implícito de los precios del carrito, porque en el pedido es informativo y no debe bloquear la venta.

## Reportes (R16–R18)
- Agregaciones en SQL (`SUM`, `GROUP BY DATE_FORMAT`), nunca trayendo pedidos a PHP. Los periodos sin ventas se rellenan con 0 para que el eje no salte meses.
- **Top 3** con Query Builder y joins: no aplica el scope de `SoftDeletes`, así que incluye productos eliminados (sus ventas son historia). Se marcan con un badge.
- Gráfica de Chart.js dentro de `wire:ignore`, iniciada con `Alpine.data`. Al cambiar el rango, Livewire despacha `grafica-actualizada` y Alpine hace `chart.update()`.
- Filtros en la URL con `#[Url]`, para compartir un reporte por enlace.

## Calidad
- Validación en el servidor siempre (Livewire `rules()`, `$request->validate` en la API); la del cliente (HTML y Alpine) es comodidad.
- `$fillable` explícito, escape de Blade y CSRF de Livewire.
- Pruebas con PHPUnit y `Livewire::test` sobre MySQL (`tienda_test`), no SQLite, para probar `lockForUpdate`, `DATE_FORMAT` y los reportes como en producción. `Http::preventStrayRequests()` impide depender de internet.
- Imágenes de marcador SVG generadas por el seeder: sin imágenes reales ni descargas.

## Fuera de alcance (y cómo se haría)
- Login con Google: Socialite con `google` en `config/services.php`.
- Reembolso automático del caso `cancelado`: método `reembolsar()` en `PasarelaPago` y webhooks de la pasarela.
- Reportes con millones de pedidos: tabla de resumen diaria llenada por un job programado en `routes/console.php`, o caché por rango en Redis.
- Búsqueda a gran escala: `LIKE '%texto%'` no usa índices; con catálogos grandes, Meilisearch o Elasticsearch vía Laravel Scout.
