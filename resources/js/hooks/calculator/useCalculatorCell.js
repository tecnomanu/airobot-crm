import { useState, useCallback, useEffect, useRef } from 'react';

/**
 * Hook para gestionar el estado de edición de una celda individual
 * @param {string} cellId - ID de la celda
 * @param {string} rawValue - Valor raw (lo que el usuario escribió, puede ser fórmula o texto)
 * @param {string} displayValue - Valor para mostrar (evaluado si es fórmula)
 * @param {function} onUpdate - Callback para actualizar celda
 * @param {function} onNavigate - Callback para navegar
 */
export function useCalculatorCell(cellId, rawValue, displayValue, onUpdate, onNavigate) {
    const [isEditing, setIsEditing] = useState(false);
    const [editValue, setEditValue] = useState('');
    const inputRef = useRef(null);
    
    // Inicializar valor de edición cuando cambia el valor de la celda
    useEffect(() => {
        if (!isEditing) {
            // Siempre editar el valor raw (puede incluir la fórmula con =)
            setEditValue(rawValue || '');
        }
    }, [rawValue, isEditing]);
    
    // Enfocar input cuando entra en modo edición
    useEffect(() => {
        if (isEditing && inputRef.current) {
            inputRef.current.focus();
            inputRef.current.select();
        }
    }, [isEditing]);
    
    // Iniciar edición
    const startEditing = useCallback(() => {
        setIsEditing(true);
        // Editar el valor raw
        setEditValue(rawValue || '');
    }, [rawValue]);
    
    // Confirmar edición
    const confirmEdit = useCallback(() => {
        if (onUpdate) {
            onUpdate(cellId, editValue);
        }
        setIsEditing(false);
    }, [cellId, editValue, onUpdate]);
    
    // Cancelar edición
    const cancelEdit = useCallback(() => {
        setIsEditing(false);
        setEditValue(rawValue || '');
    }, [rawValue]);
    
    // Manejar teclas
    const handleKeyDown = useCallback((e) => {
        if (!isEditing) {
            // Si se escribe cualquier carácter imprimible, iniciar edición automáticamente
            if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey && 
                !['Enter', 'Tab', 'Escape', 'Delete', 'Backspace'].includes(e.key) &&
                !e.key.startsWith('Arrow') && !e.key.startsWith('F')) {
                e.preventDefault();
                e.stopPropagation();
                setIsEditing(true);
                setEditValue(e.key);
                // Enfocar el input inmediatamente
                setTimeout(() => {
                    if (inputRef.current) {
                        inputRef.current.focus();
                        inputRef.current.setSelectionRange(1, 1);
                    }
                }, 0);
                return;
            }
            
            // Atajos cuando no está editando
            if (e.key === 'Enter' || e.key === 'F2') {
                e.preventDefault();
                e.stopPropagation();
                startEditing();
            } else if (e.key === 'Delete' || e.key === 'Backspace') {
                // Dejar que el hook global lo maneje
                return;
            } else if (e.key.startsWith('Arrow')) {
                e.preventDefault();
                e.stopPropagation();
                // Navegación con flechas
                if (onNavigate) {
                    onNavigate(e.key, e.shiftKey);
                }
            } else if (e.key === 'Tab') {
                e.preventDefault();
                e.stopPropagation();
                // Tab navega a la siguiente celda sin editar
                if (onNavigate) {
                    onNavigate(e.shiftKey ? 'ArrowLeft' : 'ArrowRight', false);
                }
            }
        } else {
            // Cuando está editando
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                confirmEdit();
                // Mover a siguiente fila después de editar
                if (onNavigate) {
                    setTimeout(() => onNavigate('ArrowDown', false), 0);
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                cancelEdit();
            } else if (e.key === 'Tab') {
                e.preventDefault();
                e.stopPropagation();
                confirmEdit();
                // Mover a siguiente columna después de editar
                if (onNavigate) {
                    setTimeout(() => onNavigate(e.shiftKey ? 'ArrowLeft' : 'ArrowRight', false), 0);
                }
            } else if (e.key.startsWith('Arrow')) {
                // Mientras se edita, confirmar y navegar
                e.preventDefault();
                e.stopPropagation();
                confirmEdit();
                if (onNavigate) {
                    setTimeout(() => onNavigate(e.key, false), 0);
                }
            }
        }
    }, [isEditing, startEditing, confirmEdit, cancelEdit, onNavigate, cellId, onUpdate]);
    
    return {
        isEditing,
        editValue,
        setEditValue,
        inputRef,
        startEditing,
        confirmEdit,
        cancelEdit,
        handleKeyDown
    };
}

