# Hooks de Excel

Esta carpeta contiene todos los hooks personalizados relacionados con el componente Excel.

## Estructura

```
excel/
├── useExcelCell.js      # Lógica de celda individual (edición, navegación)
├── useExcelGrid.js      # Estado y lógica principal del grid
├── useExcelFormat.js    # Formateo de celdas
├── useExcelKeyboard.js  # Atajos de teclado globales
├── useCSVImport.js      # Importación de archivos CSV
└── README.md            # Este archivo
```

## Hooks

### `useExcelCell`

Gestiona el estado de edición y navegación de una celda individual.

**Props:**
- `cellId` - ID de la celda (ej: "A1")
- `rawValue` - Valor raw (lo que el usuario escribió)
- `displayValue` - Valor para mostrar (evaluado si es fórmula)
- `onUpdate` - Callback para actualizar celda
- `onNavigate` - Callback para navegar con teclado

**Returns:**
- `isEditing` - Estado de edición
- `editValue` - Valor actual del input
- `setEditValue` - Setter del valor
- `inputRef` - Ref para el input
- `startEditing` - Inicia edición
- `confirmEdit` - Confirma edición
- `cancelEdit` - Cancela edición
- `handleKeyDown` - Handler de teclado

**Uso:**
```jsx
import { useExcelCell } from '@/hooks/excel/useExcelCell';

const { isEditing, editValue, startEditing, confirmEdit } = useExcelCell(
    cellId,
    rawValue,
    displayValue,
    onUpdate,
    onNavigate
);
```

### `useExcelGrid`

Hook principal que gestiona el estado completo del grid Excel.

**Returns:**
- **Estado**:
  - `cells` - Objeto con todas las celdas
  - `columns` - Array de columnas (letras)
  - `rows` - Array de filas (números)
  - `selectedCell` - Celda actualmente seleccionada
  - `selectedRange` - Rango de celdas seleccionado
  - `sortConfig` - Configuración de ordenamiento
  - `clipboard` - Contenido del portapapeles

- **Acciones de celda**:
  - `updateCell(cellId, value, format)` - Actualiza una celda
  - `updateCellFormat(cellIds, formatUpdates)` - Actualiza formato

- **Acciones de filas/columnas**:
  - `addRow(afterIndex)` - Agrega fila
  - `deleteRow(rowNum)` - Elimina fila
  - `addColumn(afterIndex)` - Agrega columna
  - `deleteColumn(colLetter)` - Elimina columna
  - `sortByCol(colLetter, direction)` - Ordena por columna

- **Clipboard**:
  - `copyCells(cellIds)` - Copia celdas
  - `pasteCells(targetCellId)` - Pega celdas
  - `clearCells(cellIds)` - Limpia celdas

- **Historial**:
  - `undo()` - Deshacer
  - `redo()` - Rehacer
  - `canUndo` - Puede deshacer
  - `canRedo` - Puede rehacer

- **Selección**:
  - `selectCell(cellId)` - Selecciona celda
  - `selectRange(startCell, endCell)` - Selecciona rango

- **Obtención de datos**:
  - `getCellRawValue(cellId)` - Obtiene valor raw
  - `getCellDisplayValue(cellId)` - Obtiene valor transformado
  - `getCellFormat(cellId)` - Obtiene formato

**Uso:**
```jsx
import { useExcelGrid } from '@/hooks/excel/useExcelGrid';

const {
    cells,
    selectedCell,
    updateCell,
    getCellRawValue,
    getCellDisplayValue,
    undo,
    redo
} = useExcelGrid();
```

### `useExcelFormat`

Hook para gestionar el formateo de celdas.

**Returns:**
- `getCellStyles(format)` - Convierte formato a estilos CSS

**Uso:**
```jsx
import { useExcelFormat } from '@/hooks/excel/useExcelFormat';

const { getCellStyles } = useExcelFormat();
const styles = getCellStyles({
    bold: true,
    italic: false,
    backgroundColor: '#ffffff'
});
```

### `useExcelKeyboard`

Gestiona atajos de teclado globales para Excel.

**Props:**
- `selectedCell` - Celda seleccionada
- `selectedRange` - Rango seleccionado
- `cells` - Todas las celdas
- `columns` - Columnas
- `rows` - Filas
- `onCopyCells` - Callback copiar
- `onPasteCells` - Callback pegar
- `onClearCells` - Callback limpiar
- `onSelectRange` - Callback seleccionar rango
- `onUndo` - Callback deshacer
- `onRedo` - Callback rehacer
- `onFormatChange` - Callback cambiar formato
- `selectedFormat` - Formato seleccionado
- `canUndo` - Puede deshacer
- `canRedo` - Puede rehacer

**Atajos soportados:**
- `Ctrl/Cmd + C` - Copiar
- `Ctrl/Cmd + V` - Pegar
- `Ctrl/Cmd + X` - Cortar
- `Delete/Backspace` - Eliminar
- `Ctrl/Cmd + Z` - Deshacer
- `Ctrl/Cmd + Y` - Rehacer
- `Ctrl/Cmd + B` - Negrita
- `Ctrl/Cmd + I` - Cursiva
- `Ctrl/Cmd + U` - Subrayado
- `Ctrl/Cmd + A` - Seleccionar todo

**Uso:**
```jsx
import { useExcelKeyboard } from '@/hooks/excel/useExcelKeyboard';

useExcelKeyboard(
    selectedCell,
    selectedRange,
    cells,
    columns,
    rows,
    copyCells,
    pasteCells,
    clearCells,
    selectRange,
    undo,
    redo,
    updateCellFormat,
    selectedFormat,
    canUndo,
    canRedo
);
```

### `useCSVImport`

Hook para importar archivos CSV al grid.

**Props:**
- `onImport` - Callback cuando se completa la importación

**Returns:**
- `isImporting` - Estado de importación
- `error` - Error si existe
- `importCSV(file)` - Función para importar
- `handleFileChange(event)` - Handler para input file
- `resetError()` - Reset error

**Uso:**
```jsx
import { useCSVImport } from '@/hooks/excel/useCSVImport';

const { isImporting, error, handleFileChange } = useCSVImport(
    ({ cells, maxRow, maxCol }) => {
        // Hacer algo con los datos importados
    }
);

<input 
    type="file" 
    accept=".csv"
    onChange={handleFileChange}
/>
```

## Flujo de Datos

### Edición de Celda

```
Usuario hace click en celda
        ↓
ExcelCell llama startEditing()
        ↓
useExcelCell establece isEditing = true
        ↓
Usuario escribe en input
        ↓
Usuario presiona Enter
        ↓
useExcelCell llama onUpdate(cellId, newValue)
        ↓
useExcelGrid actualiza cells[cellId]
        ↓
ExcelCell se re-renderiza con nuevo valor
```

### Navegación con Teclado

```
Usuario presiona flecha
        ↓
useExcelCell llama onNavigate(direction, shiftKey)
        ↓
ExcelGrid calcula nueva celda
        ↓
Si Shift está presionado:
    → selectRange(start, end)
Sino:
    → selectCell(newCellId)
```

### Atajos de Teclado

```
Usuario presiona Ctrl+C
        ↓
useExcelKeyboard detecta el evento
        ↓
Llama onCopyCells(selectedCells)
        ↓
useExcelGrid guarda en clipboard
```

## Convenciones

1. **Naming**: Todos los hooks empiezan con `use`
2. **Params**: Los callbacks se pasan como `onX`
3. **Returns**: Devuelven objetos con propiedades descriptivas
4. **Side effects**: Usan `useEffect` para side effects
5. **Refs**: Usan `useRef` para referencias DOM

## Mejores Prácticas

1. **Separación de responsabilidades**: Cada hook tiene una responsabilidad única
2. **Composición**: Los hooks se componen entre sí
3. **Callbacks estables**: Usar `useCallback` para callbacks
4. **Memoización**: Usar `useMemo` para cálculos costosos
5. **Cleanup**: Siempre limpiar efectos con return en `useEffect`

## Testing

Para probar los hooks:

```javascript
import { renderHook, act } from '@testing-library/react-hooks';
import { useExcelGrid } from '@/hooks/excel/useExcelGrid';

test('updateCell should update cell value', () => {
    const { result } = renderHook(() => useExcelGrid());
    
    act(() => {
        result.current.updateCell('A1', '100');
    });
    
    expect(result.current.getCellRawValue('A1')).toBe('100');
});
```

