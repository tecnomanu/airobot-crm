import { useState } from 'react';
import { toast } from 'sonner';
import { handleApiError } from '@/lib/api';

/**
 * Hook para realizar peticiones API con manejo de estado y errores
 * Compatible con el formato de respuestas estandarizado
 * 
 * @returns {Object} Objeto con función request, estado de carga y error
 */
export function useApiRequest() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    /**
     * Realiza una petición API
     * @param {Function} requestFn - Función que devuelve una promesa (ej: axios.post)
     * @param {Object} options - Opciones
     * @param {Function} options.onSuccess - Callback de éxito
     * @param {Function} options.onError - Callback de error
     * @param {string} options.successMessage - Mensaje de éxito (toast)
     * @param {string} options.errorMessage - Mensaje de error (toast)
     * @param {boolean} options.showToast - Mostrar toasts automáticamente (default: true)
     */
    const request = async (requestFn, options = {}) => {
        const {
            onSuccess,
            onError,
            successMessage,
            errorMessage,
            showToast = true,
        } = options;

        setLoading(true);
        setError(null);

        try {
            const response = await requestFn();
            const data = response.data;

            // Verificar si la respuesta tiene el formato esperado
            if (data.success) {
                if (showToast && (successMessage || data.message)) {
                    toast.success(successMessage || data.message);
                }
                if (onSuccess) {
                    onSuccess(data.data, data.metadata);
                }
                return { success: true, data: data.data, metadata: data.metadata };
            } else {
                // Respuesta con success: false
                const errorMsg = data.message || data.error || 'Error desconocido';
                setError(errorMsg);
                if (showToast) {
                    toast.error(errorMessage || errorMsg);
                }
                if (onError) {
                    onError(errorMsg);
                }
                return { success: false, error: errorMsg };
            }
        } catch (err) {
            const errorMsg = handleApiError(err);
            setError(errorMsg);
            
            if (showToast) {
                toast.error(errorMessage || errorMsg);
            }
            
            if (onError) {
                onError(errorMsg);
            }
            
            return { success: false, error: errorMsg };
        } finally {
            setLoading(false);
        }
    };

    return {
        request,
        loading,
        error,
        setError,
    };
}

