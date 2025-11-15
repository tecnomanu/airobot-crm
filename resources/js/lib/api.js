/**
 * Utilidades para manejo de respuestas API estandarizadas
 * 
 * Formato esperado de respuestas:
 * Success: { success: true, message: "", data: {}, metadata: {} }
 * Error: { success: false, message: "", error: "" }
 */

/**
 * Procesa una respuesta API exitosa
 * @param {Object} response - Respuesta de la API
 * @returns {Object} Los datos de la respuesta
 */
export function handleApiSuccess(response) {
    if (response.success) {
        return response.data || {};
    }
    throw new Error(response.message || 'Unknown error');
}

/**
 * Procesa un error de API
 * @param {Object} error - Error de la API
 * @returns {string} Mensaje de error
 */
export function handleApiError(error) {
    if (error.response?.data) {
        const data = error.response.data;
        return data.message || data.error || 'Error desconocido';
    }
    return error.message || 'Error de conexión';
}

/**
 * Extrae el mensaje de una respuesta API (success o error)
 * @param {Object} response - Respuesta de la API
 * @returns {string} Mensaje
 */
export function getApiMessage(response) {
    if (response?.success) {
        return response.message || 'Operación exitosa';
    }
    return response?.message || response?.error || 'Error desconocido';
}

/**
 * Verifica si una respuesta API fue exitosa
 * @param {Object} response - Respuesta de la API
 * @returns {boolean}
 */
export function isApiSuccess(response) {
    return response?.success === true;
}

