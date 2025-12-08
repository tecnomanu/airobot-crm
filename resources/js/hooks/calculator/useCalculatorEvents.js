import { useRef, useCallback } from 'react';
import { toast } from 'sonner';
import axios from 'axios';

/**
 * Hook para emitir eventos de cambios locales al servidor
 * Usa axios que tiene interceptor automático para refrescar CSRF token
 */
export function useCalculatorEvents(calculatorId, currentVersion, onVersionUpdate) {
    const pendingChangesRef = useRef(new Map());
    const timeoutRef = useRef(null);
    const isProcessingRef = useRef(false);
    const currentVersionRef = useRef(currentVersion);

    // Actualizar la referencia de versión cuando cambie
    currentVersionRef.current = currentVersion;

    /**
     * Emitir actualización de celda(s)
     */
    const emitCellUpdate = useCallback(async (cells, version) => {
        if (!calculatorId) {
            console.warn('No hay calculatorId, omitiendo guardado');
            return null;
        }

        try {
            const url = route('api.admin.calculator.update-cells', calculatorId);
            console.log('Emitiendo actualización de celda(s) a:', url);
            
            const response = await axios.post(url, {
                cells: Array.isArray(cells) ? cells : [cells],
                version: currentVersionRef.current,
            });

            return response.data.version;
        } catch (error) {
            if (error.response?.status === 409) {
                toast.error('Conflicto de versión. Recargando...');
                setTimeout(() => window.location.reload(), 1500);
                return null;
            }
            console.error('Error al emitir actualización de celda:', error);
            toast.error('Error al guardar cambios');
            return null;
        }
    }, [calculatorId]);

    /**
     * Emitir cambio de ancho de columna
     */
    const emitColumnResize = useCallback(async (column, width, version) => {
        if (!calculatorId) {
            return null;
        }

        try {
            const url = route('api.admin.calculator.update-column-width', { id: calculatorId, column });
            const response = await axios.put(url, {
                width,
                version: currentVersionRef.current,
            });
            
            // Actualizar versión local
            if (response.data.version && onVersionUpdate) {
                onVersionUpdate(response.data.version);
            }
            
            return response.data.version;
        } catch (error) {
            if (error.response?.status === 409) {
                toast.error('Conflicto de versión. Recargando...');
                setTimeout(() => window.location.reload(), 1500);
                return null;
            }
            console.error('Error al emitir resize de columna:', error);
            toast.error('Error al guardar ancho de columna');
            return null;
        }
    }, [calculatorId, onVersionUpdate]);

    /**
     * Emitir cambio de altura de fila
     */
    const emitRowResize = useCallback(async (row, height, version) => {
        if (!calculatorId) {
            return null;
        }

        try {
            const url = route('api.admin.calculator.update-row-height', { id: calculatorId, row });
            const response = await axios.put(url, {
                height,
                version: currentVersionRef.current,
            });
            
            // Actualizar versión local
            if (response.data.version && onVersionUpdate) {
                onVersionUpdate(response.data.version);
            }
            
            return response.data.version;
        } catch (error) {
            if (error.response?.status === 409) {
                toast.error('Conflicto de versión. Recargando...');
                setTimeout(() => window.location.reload(), 1500);
                return null;
            }
            console.error('Error al emitir resize de fila:', error);
            toast.error('Error al guardar altura de fila');
            return null;
        }
    }, [calculatorId, onVersionUpdate]);

    /**
     * Emitir movimiento de cursor (presencia)
     */
    const emitCursorMove = useCallback(async (cellId, userColor) => {
        if (!calculatorId) {
            return;
        }

        try {
            const url = route('api.admin.calculator.move-cursor', calculatorId);
            await axios.post(url, { cellId, userColor });
        } catch (error) {
            // Silencioso - no es crítico
            console.debug('Error al emitir cursor:', error);
        }
    }, [calculatorId]);

    /**
     * Agregar cambio a la cola (con debounce)
     */
    const queueCellChange = useCallback((cellId, value, format) => {
        if (!calculatorId) {
            console.warn('No hay calculatorId, omitiendo cola de cambios');
            return;
        }

        pendingChangesRef.current.set(cellId, { cellId, value, format });

        // Limpiar timeout anterior
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        // Programar procesamiento con debounce de 500ms
        timeoutRef.current = setTimeout(async () => {
            if (isProcessingRef.current) {
                return;
            }

            isProcessingRef.current = true;
            const changes = Array.from(pendingChangesRef.current.values());
            pendingChangesRef.current.clear();

            const newVersion = await emitCellUpdate(changes, currentVersionRef.current);
            
            // Actualizar versión local después de guardado exitoso
            if (newVersion !== null && onVersionUpdate) {
                currentVersionRef.current = newVersion;
                onVersionUpdate(newVersion);
            }
            
            isProcessingRef.current = false;
        }, 500);
    }, [calculatorId, emitCellUpdate, onVersionUpdate]);

    return {
        emitCellUpdate,
        emitColumnResize,
        emitRowResize,
        emitCursorMove,
        queueCellChange,
    };
}

