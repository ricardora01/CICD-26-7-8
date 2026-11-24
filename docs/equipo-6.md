# Equipo 6 — Evaluador de calificaciones por unidades

## Problema que resuelve

Un alumno tiene una materia dividida en unidades. Cada unidad tiene una calificación y un porcentaje (ponderación) dentro de la calificación final. La API dice si la materia queda **acreditada**, cuál es la calificación final, el nivel de desempeño y qué unidades le faltan y por cuántos puntos.

## Endpoint

| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/api/equipo-6/procesar` | Evalúa las calificaciones |
| `GET`, `PUT`, `PATCH`, `DELETE` | `/api/equipo-6/procesar` | Responde 405 (solo se acepta POST) |

Controlador: `app/Http/Controllers/Equipo6Controller.php`

## Datos de entrada (JSON)

```json
{
  "materia": "Seguridad en TI",
  "unidades": [
    { "calificacion": 92,   "ponderacion": 30 },
    { "calificacion": 65,   "ponderacion": 30 },
    { "calificacion": 88.5, "ponderacion": 40 }
  ]
}
```

| Campo | Obligatorio | Regla |
|---|---|---|
| `materia` | No | Texto de 3 a 100 caracteres. Solo letras, números, espacios, puntos y guiones. Puede ser `null`. |
| `unidades` | Sí | Lista (no objeto) de 1 a 8 unidades. |
| `unidades.*` | Sí | Cada unidad solo puede tener `calificacion` y `ponderacion` (otros campos se rechazan). |
| `unidades.*.calificacion` | Sí | Número de 0 a 100, máximo 2 decimales. |
| `unidades.*.ponderacion` | Sí | Entero de 1 a 100. |

## Reglas de negocio

1. La suma de todas las ponderaciones debe ser **exactamente 100**.
2. Una unidad se acredita con **70 o más**.
3. La materia se acredita solo si **todas** las unidades están acreditadas. Si alguna unidad tiene menos de 70, la materia queda **NO ACREDITADA**, aunque el promedio sea alto.
4. Promedio ponderado = suma de (calificación × ponderación / 100), redondeado a 2 decimales.
5. Calificación final = promedio redondeado a entero (solo si la materia está acreditada; si no, es `null`).
6. Nivel de desempeño (según la calificación final):

| Calificación final | Nivel |
|---|---|
| 95 – 100 | Excelente |
| 85 – 94 | Notable |
| 75 – 84 | Bueno |
| 70 – 74 | Suficiente |
| No acreditada | Desempeño insuficiente |

7. Para cada unidad no acreditada se calculan los puntos que le faltan para llegar a 70.

## Respuestas

### 200 — Éxito

```json
{
  "success": true,
  "data": {
    "materia": "Seguridad en TI",
    "estatus": "NO ACREDITADA",
    "calificacion_final": null,
    "promedio_ponderado": 82.5,
    "nivel_desempeno": "Desempeño insuficiente",
    "total_unidades": 3,
    "unidades_acreditadas": 2,
    "unidades_pendientes": [2],
    "detalle": [
      { "unidad": 1, "calificacion": 92,   "ponderacion": 30, "aporte": 27.6, "acreditada": true,  "puntos_faltantes": 0 },
      { "unidad": 2, "calificacion": 65,   "ponderacion": 30, "aporte": 19.5, "acreditada": false, "puntos_faltantes": 5 },
      { "unidad": 3, "calificacion": 88.5, "ponderacion": 40, "aporte": 35.4, "acreditada": true,  "puntos_faltantes": 0 }
    ]
  }
}
```

### 422 — Datos inválidos o reglas de negocio no cumplidas

```json
{
  "success": false,
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "unidades": ["La suma de las ponderaciones debe ser exactamente 100. Suma recibida: 99."]
  }
}
```

### 405 — Método no permitido

```json
{ "success": false, "message": "Método no permitido. Usa POST para consumir este endpoint." }
```

### 500 — Error inesperado

```json
{ "success": false, "message": "Ocurrió un error interno al procesar la solicitud." }
```

El detalle técnico del error solo se guarda en el log del servidor; nunca se envía al cliente.

## Seguridad

- No usa base de datos, credenciales ni datos personales.
- Rechaza campos extra dentro de cada unidad (evita que se inyecten datos que no esperamos).
- Limita el tamaño de la entrada (máximo 8 unidades, materia de máximo 100 caracteres).
- Rechaza HTML/scripts en `materia`.
- Todas las respuestas son JSON con la misma estructura (`success`, `message`, `errors` / `data`).
- Los errores internos no muestran rutas, clases ni trazas.

## Ejemplo con curl

```bash
curl -X POST https://<servidor>/api/equipo-6/procesar \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"materia":"Seguridad en TI","unidades":[{"calificacion":92,"ponderacion":30},{"calificacion":65,"ponderacion":30},{"calificacion":88.5,"ponderacion":40}]}'
```

## Pruebas

| Tipo | Archivo |
|---|---|
| Unitarias (lógica) | `tests/Unit/Equipo6LogicaTest.php` |
| Funcionales (API) | `tests/Feature/Equipo6ApiTest.php` |
