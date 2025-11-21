# Librería de Utilidades para Excel

Esta carpeta contiene todas las utilidades, funciones y formateadores relacionados con el componente Excel del proyecto.

## Estructura

```
excel/
├── formatters.js    # Formateadores de valores de celda
├── formulas.js      # Sistema de evaluación de fórmulas
├── utils.js         # Utilidades generales (coordenadas, rangos, CSV, etc.)
└── README.md        # Este archivo
```

## Archivos

### `formatters.js`

Funciones para formatear y transformar valores de celdas según diferentes tipos.

**Exports principales:**
- `formatCellValue(value, format)` - Formatea un valor según el tipo especificado
- Soporta: text, number, currency, percentage, date

**Uso:**
```javascript
import { formatCellValue } from '@/lib/excel/formatters';

const formatted = formatCellValue(1234.56, 'currency');
// Resultado: "$1,234.56"
```

### `formulas.js`

Sistema completo de evaluación de fórmulas tipo Excel.

**Exports principales:**
- `evaluateFormula(formula, cells)` - Evalúa una fórmula con el contexto de celdas
- `isFormula(value)` - Verifica si un valor es una fórmula (empieza con =)
- `getFormulaDependencies(formula)` - Obtiene las celdas referenciadas en una fórmula

**Funciones soportadas:**
- `SUM(rango)` - Suma de valores
- `AVERAGE(rango)` - Promedio
- `MIN(rango)` - Valor mínimo
- `MAX(rango)` - Valor máximo
- `COUNT(rango)` - Cuenta celdas con datos
- `COUNTA(rango)` - Cuenta celdas no vacías
- `COUNTBLANK(rango)` - Cuenta celdas vacías

**Operaciones soportadas:**
- Suma (`+`)
- Resta (`-`)
- Multiplicación (`*`)
- División (`/`)
- Paréntesis para orden de operaciones

**Uso:**
```javascript
import { evaluateFormula, isFormula } from '@/lib/excel/formulas';

const cells = {
    A1: { value: 10 },
    A2: { value: 20 },
    A3: { value: 30 }
};

const result = evaluateFormula('=SUM(A1:A3)', cells);
// Resultado: 60

if (isFormula('=A1+A2')) {
    // Es una fórmula
}
```

### `utils.js`

Utilidades generales para manejo de coordenadas, rangos, CSV y ordenamiento.

**Exports principales:**

#### Conversión de Coordenadas
- `cellToCoords(cellId)` - Convierte "A1" a `{ row: 1, col: 'A' }`
- `coordsToCell(coords)` - Convierte `{ row: 1, col: 'A' }` a "A1"
- `indexToColumn(index)` - Convierte número a letra de columna (1 → 'A', 27 → 'AA')
- `columnToIndex(col)` - Convierte letra a número ('A' → 1, 'AA' → 27)

#### Rangos de Celdas
- `getCellRange(start, end)` - Obtiene array de IDs de celdas en un rango
- `isInRange(cellId, start, end)` - Verifica si una celda está en un rango

#### Importación/Exportación CSV
- `parseCSV(csvText)` - Parsea texto CSV a objeto de celdas
- `exportToCSV(cells, maxRow, maxCol)` - Exporta celdas a formato CSV

#### Ordenamiento
- `sortByColumn(data, column, direction)` - Ordena datos por columna

**Uso:**
```javascript
import { cellToCoords, getCellRange, parseCSV } from '@/lib/excel/utils';

// Convertir celda a coordenadas
const coords = cellToCoords('B5');
// Resultado: { row: 5, col: 'B' }

// Obtener rango de celdas
const range = getCellRange('A1', 'C3');
// Resultado: ['A1', 'A2', 'A3', 'B1', 'B2', 'B3', 'C1', 'C2', 'C3']

// Parsear CSV
const csvText = "Nombre,Edad\nJuan,30\nMaría,25";
const { cells } = parseCSV(csvText);
// Resultado: objeto con celdas A1, B1, A2, B2, A3, B3
```

## Importación

Todos los archivos se importan desde `@/lib/excel/`:

```javascript
// Formatters
import { formatCellValue } from '@/lib/excel/formatters';

// Formulas
import { evaluateFormula, isFormula } from '@/lib/excel/formulas';

// Utils
import { 
    cellToCoords, 
    coordsToCell, 
    getCellRange,
    parseCSV,
    exportToCSV 
} from '@/lib/excel/utils';
```

## Uso en Componentes

### En Componentes de Excel
```javascript
// resources/js/Components/Excel/ExcelCell.jsx
import { formatCellValue } from '@/lib/excel/formatters';

const displayValue = formatCellValue(value, format);
```

### En Hooks
```javascript
// resources/js/hooks/useExcelGrid.js
import { evaluateFormula, isFormula } from '@/lib/excel/formulas';
import { cellToCoords, coordsToCell } from '@/lib/excel/utils';

// Usar en lógica del hook
```

## Convenciones

1. **Nomenclatura de Celdas**: Siempre usar formato `COLUMNA + FILA` (ej: "A1", "Z100", "AA5")
2. **Coordenadas**: Usar objetos `{ row: number, col: string }`
3. **Rangos**: Siempre especificar como `"INICIO:FIN"` (ej: "A1:B10")
4. **Fórmulas**: Siempre empezar con `=` (ej: "=SUM(A1:A10)")
5. **Valores Vacíos**: Tratar como `0` en operaciones matemáticas

## Mantenimiento

Al agregar nuevas funcionalidades:

1. **Formatters**: Agregar nuevos tipos de formato en `formatters.js`
2. **Formulas**: Agregar nuevas funciones en el objeto `functions` en `formulas.js`
3. **Utils**: Agregar utilidades generales en `utils.js`
4. **Documentar**: Actualizar este README con las nuevas funciones

## Testing

Para probar las utilidades:

```javascript
// Ejemplo de test manual
import { evaluateFormula } from '@/lib/excel/formulas';

const cells = {
    A1: { value: 10 },
    A2: { value: 20 }
};

console.log(evaluateFormula('=A1+A2', cells)); // 30
console.log(evaluateFormula('=SUM(A1:A2)', cells)); // 30
console.log(evaluateFormula('=(A1+A2)*2', cells)); // 60
```

