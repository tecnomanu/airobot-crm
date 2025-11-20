import React from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Check, X } from 'lucide-react';

export default function ExcelFormulaBar({ 
    selectedCell, 
    cellValue = '', 
    cellFormula = null,
    onValueChange,
    onConfirm,
    onCancel
}) {
    // Si hay fórmula, mostrar la fórmula; si no, mostrar el valor
    const displayValue = cellFormula || cellValue;
    const [editValue, setEditValue] = React.useState(displayValue);
    
    React.useEffect(() => {
        setEditValue(displayValue);
    }, [displayValue, selectedCell]);
    
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
            <div className="flex items-center gap-2 min-w-[80px]">
                <span className="text-sm font-mono text-gray-600">{selectedCell || 'A1'}</span>
                <span className="text-gray-400">:</span>
            </div>
            <div className="flex-1 flex items-center gap-1">
                <Input
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

