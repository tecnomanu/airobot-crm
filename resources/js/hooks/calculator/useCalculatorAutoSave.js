import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import axios from 'axios';

/**
 * Hook para guardado automático del estado del Calculator
 * Usa axios que tiene interceptor automático para refrescar CSRF token
 */
export function useCalculatorAutoSave(calculatorId, state, enabled = true, delay = 800) {
    const timeoutRef = useRef(null);
    const [isSaving, setIsSaving] = useState(false);
    const previousStateRef = useRef(null);
    const isInitialMount = useRef(true);

    useEffect(() => {
        // No hacer nada si no hay ID o está deshabilitado
        if (!calculatorId || !enabled) {
            return;
        }

        // Inicializar el estado anterior en el primer montaje
        if (isInitialMount.current) {
            previousStateRef.current = JSON.stringify(state);
            isInitialMount.current = false;
            return;
        }

        // Verificar si el estado ha cambiado
        const currentState = JSON.stringify(state);
        if (currentState === previousStateRef.current) {
            return;
        }

        // Limpiar timeout anterior
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        // Programar guardado con debounce
        timeoutRef.current = setTimeout(async () => {
            if (isSaving) {
                return;
            }

            try {
                setIsSaving(true);

                // Guardar el estado actual usando axios (CSRF handling automático)
                const response = await axios.put(
                    route('api.admin.calculator.save-state', calculatorId),
                    state
                );

                if (response.data.success) {
                    // Actualizar referencia del estado anterior solo si fue exitoso
                    previousStateRef.current = currentState;
                } else {
                    throw new Error(response.data.message || 'Error desconocido');
                }
            } catch (error) {
                console.error('Error al guardar automáticamente:', error);
                const message = error.response?.data?.message || error.message;
                toast.error('Error al guardar cambios: ' + message);
            } finally {
                setIsSaving(false);
            }
        }, delay);

        // Cleanup
        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, [calculatorId, state, enabled, delay, isSaving]);

    // Cleanup al desmontar
    useEffect(() => {
        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, []);

    return {
        isSaving
    };
}

