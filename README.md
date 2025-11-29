# Mapa de Clientes Web

Aplicación web que muestra en un mapa la ubicación de los clientes y/o servicios, para apoyar la operación de campo y el seguimiento de rutas de los técnicos. (Proyecto anexado a Tecnicliente con web socket)

Este proyecto está construido con **PHP**, **JavaScript**, **CSS** y algunos scripts en **Python** para el procesamiento de datos.

## Objetivo

Proporcionar una vista geográfica de la información de clientes (por ejemplo: contratos activos, servicios pendientes, incidencias), permitiendo:

- Visualizar rápidamente la distribución de clientes en un mapa.
- Filtrar por zona, estatus u otros criterios.
- Consultar datos básicos del cliente al hacer clic en un marcador.
- (Opcional) Integrarse con el sistema de rutas/técnicos (por ejemplo, la app móvil *TecniCliente*).

## Tecnologías utilizadas

- **Frontend**
  - HTML
  - CSS
  - JavaScript
  - Biblioteca de mapas (por ejemplo, Google Maps o Leaflet) – especificar la que uses.

- **Backend**
  - PHP (endpoints para obtener los datos de clientes, zonas, etc.)
  - (Opcional) Base de datos MySQL u otra – detallar si aplica.

- **Herramientas de datos**
  - Python (scripts para importar/exportar o transformar información de clientes).

---

## Estructura del proyecto

```text
mapaclienetweb/
├── mapa1/
│   ├── index.html / index.php        # Página principal del mapa
│   ├── js/                           # Scripts de JavaScript relacionados con el mapa
│   ├── css/                          # Hojas de estilo
│   ├── api/                          # (Opcional) Endpoints PHP que devuelven datos en JSON
│   ├── data/                         # (Opcional) Archivos CSV / JSON / GeoJSON con clientes
│   └── scripts/                      # (Opcional) Scripts Python de apoyo
└── __MACOSX/                         # Carpeta generada por macOS (no necesaria en producción)
