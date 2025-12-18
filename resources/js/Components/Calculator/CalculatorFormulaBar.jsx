import React from 'react';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Check, X } from 'lucide-react';

export default function CalculatorFormulaBar({ 
    selectedCell, 
    selectedRange = null,
    cellValue = '', 
    onValueChange,
    onConfirm,
    onCancel,
    onFormulaMode,
    isEditingFormula = false
}) {
    // cellValue siempre contiene el valor raw (puede incluir fórmula con =)
    const [editValue, setEditValue] = React.useState(cellValue);
    const inputRef = React.useRef(null);
    
    // Inicializar objeto global
    React.useEffect(() => {
        if (!window.__calculatorFormulaBar) {
            window.__calculatorFormulaBar = {};
        }
    }, []);
    
    React.useEffect(() => {
        setEditValue(cellValue);
    }, [cellValue, selectedCell]);
    
    // Determinar qué mostrar en el indicador de celda
    const cellLabel = React.useMemo(() => {
        if (selectedRange && selectedRange.start !== selectedRange.end) {
            return `${selectedRange.start}:${selectedRange.end}`;
        }
        return selectedCell || 'A1';
    }, [selectedCell, selectedRange]);
    
    // Detectar si estamos editando una fórmula
    const isFormula = editValue.startsWith('=');
    
    // Notificar cambio de modo fórmula
    React.useEffect(() => {
        if (onFormulaMode) {
            onFormulaMode(isFormula);
        }
    }, [isFormula, onFormulaMode]);
    
    // Método para agregar referencia de celda a la fórmula
    const addCellReference = React.useCallback((cellId) => {
        if (isFormula) {
            const newValue = editValue + cellId;
            setEditValue(newValue);
            if (onValueChange) {
                onValueChange(newValue);
            }
            // Mantener el foco en el input
            setTimeout(() => inputRef.current?.focus(), 0);
        }
    }, [isFormula, editValue, onValueChange]);
    
    // Exponer la función al padre
    React.useEffect(() => {
        if (window.__calculatorFormulaBar) {
            window.__calculatorFormulaBar.addCellReference = addCellReference;
        }
    }, [addCellReference]);
    
    const handleKeyDown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (onConfirm) {
                onConfirm(editValue);
            }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            setEditValue(cellValue);
            if (onCancel) {
                onCancel();
            }
        }
    };
    
    const handleConfirm = () => {
        if (onConfirm) {
            onConfirm(editValue);
        }
    };
    
    const handleCancel = () => {
        setEditValue(cellValue);
        if (onCancel) {
            onCancel();
        }
    };
    
    return (
        <div className="flex items-center gap-2 px-2 py-1 border-b border-gray-300 bg-white">
            <div className="flex items-center justify-start w-32 flex-shrink-0">
                <span className="text-sm font-mono font-semibold text-gray-700 truncate">{cellLabel}</span>
            </div>
            <div className="flex-1 flex items-center gap-1">
                <Input
                    ref={inputRef}
                    value={editValue}
                    onChange={(e) => {
                        setEditValue(e.target.value);
                        if (onValueChange) {
                            onValueChange(e.target.value);
                        }
                    }}
                    onKeyDown={handleKeyDown}
                    className="h-8 font-mono text-sm"
                    placeholder="Ingrese valor o fórmula"
                />
                <Button
                    size="sm"
                    variant="ghost"
                    onClick={handleConfirm}
                    className="h-8 w-8 p-0"
                >
                    <Check className="h-4 w-4" />
                </Button>
                <Button
                    size="sm"
                    variant="ghost"
                    onClick={handleCancel}
                    className="h-8 w-8 p-0"
                >
                    <X className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}

