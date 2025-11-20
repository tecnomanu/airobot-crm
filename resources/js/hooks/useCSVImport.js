import { useState, useCallback } from 'react';
import { parseCSV } from '@/lib/excelUtils';

/**
 * Hook para gestionar la importación de archivos CSV
 */
export function useCSVImport(onImport) {
    const [isImporting, setIsImporting] = useState(false);
    const [error, setError] = useState(null);
    
    /**
     * Procesa un archivo CSV y lo importa al grid
     */
    const importCSV = useCallback(async (file) => {
        setIsImporting(true);
        setError(null);
        
        try {
            // Leer archivo como texto
            const text = await readFileAsText(file);
            
            // Parsear CSV
            const { cells, maxRow, maxCol } = parseCSV(text);
            
            // Llamar callback con datos parseados
            if (onImport) {
                onImport({ cells, maxRow, maxCol });
            }
            
            setIsImporting(false);
            return { success: true, cells, maxRow, maxCol };
        } catch (err) {
            const errorMessage = err.message || 'Error al importar el archivo CSV';
            setError(errorMessage);
            setIsImporting(false);
            return { success: false, error: errorMessage };
        }
    }, [onImport]);
    
    /**
     * Reset error state
     */
    const resetError = useCallback(() => {
        setError(null);
    }, []);
    
    /**
     * Lee un archivo como texto
     */
    const readFileAsText = useCallback((file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                resolve(e.target.result);
            };
            
            reader.onerror = () => {
                reject(new Error('Error al leer el archivo'));
            };
            
            // Intentar leer como UTF-8
            reader.readAsText(file, 'UTF-8');
        });
    }, []);
    
    /**
     * Maneja el cambio de archivo en input file
     */
    const handleFileChange = useCallback((e) => {
        const file = e.target.files?.[0];
        if (file) {
            // Validar extensión
            if (!file.name.toLowerCase().endsWith('.csv')) {
                setError('El archivo debe ser un CSV (.csv)');
                return;
            }
            
            importCSV(file);
        }
    }, [importCSV]);
    
    return {
        isImporting,
        error,
        importCSV,
        handleFileChange,
        resetError
    };
}

