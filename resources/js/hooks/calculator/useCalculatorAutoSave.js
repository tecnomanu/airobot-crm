import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

/**
 * Hook para guardado automático del estado del Calculator
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

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!csrfToken) {
                    console.error('CSRF token not found');
                    toast.error('Error: Token de seguridad no encontrado');
                    return;
                }

                // Guardar el estado actual
                const response = await fetch(route('api.admin.calculator.save-state', calculatorId), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(state),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();

                if (result.success) {
                    // Actualizar referencia del estado anterior solo si fue exitoso
                    previousStateRef.current = currentState;
                } else {
                    throw new Error(result.message || 'Error desconocido');
                }
            } catch (error) {
                console.error('Error al guardar automáticamente:', error);
                toast.error('Error al guardar cambios: ' + error.message);
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

