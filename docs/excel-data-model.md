# Modelo de Datos de Excel

## Arquitectura Simplificada

El componente Excel utiliza un modelo de datos simplificado basado en el patrón **Transformer**:

### Concepto

- **Storage**: Guardamos solo el **valor raw** (lo que el usuario escribió)
- **Presentation**: Aplicamos un **transformer** al obtener el valor para mostrarlo

## Estructura de Celda

```javascript
{
    value: '=A1+A2',  // Valor raw (puede ser texto, número o fórmula)
    format: {         // Formato de presentación
        backgroundColor: '#ffffff',
        textColor: '#000000',
        fontSize: 12,
        fontFamily: 'Arial',
        bold: false,
        italic: false,
        underline: false,
        align: 'left',
        format: 'text'
    }
}
```

### ✅ Ventajas del Modelo

1. **Simplicidad**: Solo un campo `value` en lugar de `value` + `formula`
2. **Única fuente de verdad**: El valor raw es siempre la fuente de verdad
3. **Fácil de mantener**: No hay sincronización entre campos
4. **Extensible**: Fácil agregar nuevos transformers sin cambiar el modelo

## Flujo de Datos

### 1. Escritura (Usuario → Storage)

```
Usuario escribe: "=SUM(A1:A10)"
        ↓
updateCell(cellId, "=SUM(A1:A10)")
        ↓
cells[cellId] = { value: "=SUM(A1:A10)" }
```

### 2. Lectura (Storage → Presentación)

```
cells[cellId].value = "=SUM(A1:A10)"
        ↓
getCellRawValue(cellId) → "=SUM(A1:A10)"  // Para edición
        ↓
getCellDisplayValue(cellId) → transformer → 100  // Para mostrar
```

## API de Funciones

### Storage (useExcelGrid)

```javascript
// Escribir valor
updateCell(cellId, value)

// Leer valor raw (para edición)
getCellRawValue(cellId)

// Leer valor para mostrar (aplica transformer)
getCellDisplayValue(cellId)
```

### Transformer Interno

```javascript
function getCellDisplayValue(cellId) {
    const rawValue = cells[cellId]?.value || '';
    
    // Si empieza con =, es una fórmula
    if (isFormula(rawValue)) {
        return evaluateFormula(rawValue, cells);
    }
    
    // Si no, devolver tal cual
    return rawValue;
}
```

## Ejemplo Completo

### Escenario: Suma de dos celdas

```javascript
// 1. Usuario escribe valores
updateCell('A1', '10');
updateCell('A2', '20');
updateCell('A3', '=A1+A2');

// 2. Estado en storage
cells = {
    A1: { value: '10' },
    A2: { value: '20' },
    A3: { value: '=A1+A2' }  // ← Solo guardamos la fórmula
}

// 3. Lectura para edición (barra de fórmulas)
getCellRawValue('A3')        // → "=A1+A2"

// 4. Lectura para mostrar (celda en grid)
getCellDisplayValue('A3')     // → 30 (evaluado)
```

## Componentes y Props

### ExcelCell

```jsx
<ExcelCell
    cellId="A1"
    rawValue="=SUM(A1:A10)"      // Valor raw (para editar)
    displayValue="150"           // Valor evaluado (para mostrar)
    format={{ ... }}
    onUpdate={updateCell}
/>
```

### ExcelFormulaBar

```jsx
<ExcelFormulaBar
    selectedCell="A1"
    cellValue="=SUM(A1:A10)"     // Valor raw (siempre)
    onConfirm={updateCell}
/>
```

## Comparación con Modelo Anterior

### ❌ Modelo Anterior (Complejo)

```javascript
{
    value: 30,           // Resultado evaluado
    formula: '=A1+A2',   // Fórmula original
    format: { ... }
}
```

**Problemas:**
- Duplicación de datos
- Necesidad de sincronizar `value` y `formula`
- Más complejo de mantener
- Mayor uso de memoria

### ✅ Modelo Actual (Simplificado)

```javascript
{
    value: '=A1+A2',    // Solo el valor raw
    format: { ... }
}
```

**Ventajas:**
- Única fuente de verdad
- Sin sincronización necesaria
- Más simple de entender
- Menos memoria

## Decisiones de Diseño

### ¿Por qué guardar el raw y no el evaluado?

1. **Edición**: El usuario necesita ver la fórmula original al editar
2. **Re-evaluación**: Las fórmulas deben re-evaluarse cuando cambian las dependencias
3. **Portabilidad**: El valor raw es portable (CSV, JSON, etc.)

### ¿Por qué no evaluar al guardar?

1. **Dependencias**: Una celda puede referenciar otras que cambien después
2. **Re-cálculo**: Necesitamos re-calcular cuando cambian las dependencias
3. **Performance**: Evaluar solo al mostrar es más eficiente

## Patrones de Uso

### Editar una Celda

```javascript
// 1. Usuario hace doble clic
const rawValue = getCellRawValue(cellId);

// 2. Mostrar en input
<input value={rawValue} />

// 3. Usuario confirma
onConfirm={(newValue) => {
    updateCell(cellId, newValue);
});
```

### Mostrar una Celda

```javascript
// 1. Obtener valores
const rawValue = getCellRawValue(cellId);
const displayValue = getCellDisplayValue(cellId);

// 2. Renderizar
<ExcelCell 
    rawValue={rawValue}
    displayValue={displayValue}
/>

// 3. Dentro de ExcelCell
{isEditing ? 
    <input value={rawValue} /> :      // Editar el raw
    <div>{displayValue}</div>         // Mostrar el evaluado
}
```

## Extensibilidad

Para agregar nuevos tipos de transformers:

```javascript
function getCellDisplayValue(cellId) {
    const rawValue = cells[cellId]?.value || '';
    
    // Transformer de fórmulas
    if (isFormula(rawValue)) {
        return evaluateFormula(rawValue, cells);
    }
    
    // Transformer de fechas (futuro)
    if (isDate(rawValue)) {
        return formatDate(rawValue);
    }
    
    // Transformer de moneda (futuro)
    if (isCurrency(rawValue)) {
        return formatCurrency(rawValue);
    }
    
    // Valor tal cual
    return rawValue;
}
```

## Referencias

- Implementación: `resources/js/hooks/useExcelGrid.js`
- Evaluación de fórmulas: `resources/js/lib/excel/formulas.js`
- Componente de celda: `resources/js/Components/Excel/ExcelCell.jsx`

