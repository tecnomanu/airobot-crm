import { useEffect, useCallback } from 'react';

/**
 * Hook centralizado para manejar todos los atajos de teclado del Calculator
 * Garantiza que los atajos funcionen de forma natural con shadcn/ui
 */
export function useCalculatorKeyboard({
    selectedCell,
    selectedRange,
    selectedFormat,
    isEditingCell = false,
    onFormatChange,
    onUndo,
    onRedo,
    onCopy,
    onPaste,
    onCut,
    onDelete,
    onSelectAll,
    onSave,
    canUndo = false,
    canRedo = false
}) {
    
    const handleGlobalKeyDown = useCallback((e) => {
        // Si estamos editando una celda, solo manejar ciertos atajos
        if (isEditingCell) {
            // Permitir Ctrl+Z, Ctrl+Y dentro de la edición
            if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
                // Dejar que el navegador maneje el undo del input
                return;
            }
            // No interceptar otros atajos mientras se edita
            return;
        }
        
        // Atajos con Ctrl/Cmd
        if (e.ctrlKey || e.metaKey) {
            switch (e.key.toLowerCase()) {
                // Formato de texto
                case 'b':
                    e.preventDefault();
                    if (onFormatChange) {
                        onFormatChange({
                            ...selectedFormat,
                            bold: !selectedFormat.bold
                        });
                    }
                    break;
                    
                case 'i':
                    e.preventDefault();
                    if (onFormatChange) {
                        onFormatChange({
                            ...selectedFormat,
                            italic: !selectedFormat.italic
                        });
                    }
                    break;
                    
                case 'u':
                    e.preventDefault();
                    if (onFormatChange) {
                        onFormatChange({
                            ...selectedFormat,
                            underline: !selectedFormat.underline
                        });
                    }
                    break;
                
                // Clipboard
                case 'c':
                    e.preventDefault();
                    if (onCopy) onCopy();
                    break;
                    
                case 'v':
                    e.preventDefault();
                    if (onPaste) onPaste();
                    break;
                    
                case 'x':
                    e.preventDefault();
                    if (onCut) onCut();
                    break;
                
                // Seleccionar todo
                case 'a':
                    e.preventDefault();
                    if (onSelectAll) onSelectAll();
                    break;
                
                // Undo/Redo
                case 'z':
                    e.preventDefault();
                    if (e.shiftKey) {
                        if (onRedo && canRedo) onRedo();
                    } else {
                        if (onUndo && canUndo) onUndo();
                    }
                    break;
                    
                case 'y':
                    e.preventDefault();
                    if (onRedo && canRedo) onRedo();
                    break;
                
                // Guardar
                case 's':
                    e.preventDefault();
                    if (onSave) onSave();
                    break;
                    
                default:
                    break;
            }
        }
        // Teclas sin modificadores
        else {
            switch (e.key) {
                case 'Delete':
                case 'Backspace':
                    // Solo prevenir si no estamos en un input
                    if (!e.target.matches('input, textarea')) {
                        e.preventDefault();
                        if (onDelete) onDelete();
                    }
                    break;
                    
                default:
                    break;
            }
        }
    }, [
        isEditingCell,
        selectedFormat,
        onFormatChange,
        onCopy,
        onPaste,
        onCut,
        onDelete,
        onSelectAll,
        onUndo,
        onRedo,
        onSave,
        canUndo,
        canRedo
    ]);
    
    useEffect(() => {
        window.addEventListener('keydown', handleGlobalKeyDown, true);
        
        return () => {
            window.removeEventListener('keydown', handleGlobalKeyDown, true);
        };
    }, [handleGlobalKeyDown]);
    
    return {
        // Retornar helpers si son necesarios
    };
}

