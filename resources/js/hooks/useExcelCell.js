import { useState, useCallback, useEffect, useRef } from 'react';

/**
 * Hook para gestionar el estado de edición de una celda individual
 */
export function useExcelCell(cellId, value, onUpdate, onNavigate) {
    const [isEditing, setIsEditing] = useState(false);
    const [editValue, setEditValue] = useState('');
    const inputRef = useRef(null);
    
    // Inicializar valor de edición cuando cambia el valor de la celda
    useEffect(() => {
        if (!isEditing) {
            setEditValue(value || '');
        }
    }, [value, isEditing]);
    
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
        setEditValue(value || '');
    }, [value]);
    
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
        setEditValue(value || '');
    }, [value]);
    
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

