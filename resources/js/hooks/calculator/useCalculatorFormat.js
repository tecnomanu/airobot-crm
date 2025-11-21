import { useState, useCallback } from 'react';

/**
 * Hook para gestionar formatos de celdas
 */
export function useCalculatorFormat() {
    const [formatPresets, setFormatPresets] = useState({
        header: {
            backgroundColor: '#f3f4f6',
            textColor: '#000000',
            fontSize: 12,
            fontFamily: 'Arial',
            bold: true,
            italic: false,
            underline: false,
            align: 'center',
            format: 'text'
        },
        number: {
            backgroundColor: '#ffffff',
            textColor: '#000000',
            fontSize: 12,
            fontFamily: 'Arial',
            bold: false,
            italic: false,
            underline: false,
            align: 'right',
            format: 'number'
        },
        currency: {
            backgroundColor: '#ffffff',
            textColor: '#000000',
            fontSize: 12,
            fontFamily: 'Arial',
            bold: false,
            italic: false,
            underline: false,
            align: 'right',
            format: 'currency'
        },
        date: {
            backgroundColor: '#ffffff',
            textColor: '#000000',
            fontSize: 12,
            fontFamily: 'Arial',
            bold: false,
            italic: false,
            underline: false,
            align: 'left',
            format: 'date'
        }
    });
    
    // Aplicar preset de formato
    const applyPreset = useCallback((presetName) => {
        return formatPresets[presetName] || null;
    }, [formatPresets]);
    
    // Crear formato personalizado
    const createFormat = useCallback((overrides = {}) => {
        return {
            backgroundColor: '#ffffff',
            textColor: '#000000',
            fontSize: 12,
            fontFamily: 'Arial',
            bold: false,
            italic: false,
            underline: false,
            align: 'left',
            format: 'text',
            ...overrides
        };
    }, []);
    
    // Obtener estilos CSS para aplicar a celda
    const getCellStyles = useCallback((format) => {
        return {
            backgroundColor: format.backgroundColor || '#ffffff',
            color: format.textColor || '#000000',
            fontSize: `${format.fontSize || 12}px`,
            fontFamily: format.fontFamily || 'Arial',
            fontWeight: format.bold ? 'bold' : 'normal',
            fontStyle: format.italic ? 'italic' : 'normal',
            textDecoration: format.underline ? 'underline' : 'none',
            textAlign: format.align || 'left'
        };
    }, []);
    
    // Colores predefinidos
    const colorPalette = [
        '#ffffff', '#000000', '#ef4444', '#f97316', '#f59e0b',
        '#eab308', '#84cc16', '#22c55e', '#10b981', '#14b8a6',
        '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6',
        '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#f3f4f6',
        '#e5e7eb', '#d1d5db', '#9ca3af', '#6b7280', '#4b5563'
    ];
    
    return {
        formatPresets,
        applyPreset,
        createFormat,
        getCellStyles,
        colorPalette
    };
}

