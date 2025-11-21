/**
 * Sistema de Notificaciones Nativas del Navegador
 * Gestiona permisos, emisión de notificaciones y sonidos
 */

class NotificationManager {
    constructor() {
        this.permission = Notification.permission;
        this.audio = null;
        this.defaultSound = '/sounds/notification.mp3';
    }

    /**
     * Verifica si las notificaciones están soportadas
     */
    isSupported() {
        return 'Notification' in window;
    }

    /**
     * Verifica si ya tenemos permiso
     */
    hasPermission() {
        return this.permission === 'granted';
    }

    /**
     * Solicita permiso para notificaciones
     */
    async requestPermission() {
        if (!this.isSupported()) {
            console.warn('Las notificaciones no están soportadas en este navegador');
            return false;
        }

        if (this.hasPermission()) {
            return true;
        }

        try {
            const permission = await Notification.requestPermission();
            this.permission = permission;
            return permission === 'granted';
        } catch (error) {
            console.error('Error al solicitar permiso de notificaciones:', error);
            return false;
        }
    }

    /**
     * Reproduce un sonido de notificación
     */
    playSound(soundUrl = null) {
        try {
            const url = soundUrl || this.defaultSound;
            this.audio = new Audio(url);
            this.audio.volume = 0.5;
            this.audio.play().catch(err => {
                console.warn('No se pudo reproducir el sonido:', err);
            });
        } catch (error) {
            console.warn('Error al reproducir sonido:', error);
        }
    }

    /**
     * Muestra una notificación nativa
     * 
     * @param {Object} options
     * @param {string} options.title - Título de la notificación
     * @param {string} options.body - Cuerpo del mensaje
     * @param {string} options.icon - URL del ícono
     * @param {string} options.tag - Tag único para evitar duplicados
     * @param {boolean} options.requireInteraction - Si requiere que el usuario la cierre
     * @param {Function} options.onClick - Callback al hacer click
     * @param {boolean} options.playSound - Si debe reproducir sonido
     * @param {string} options.soundUrl - URL personalizada del sonido
     */
    async show({
        title,
        body,
        icon = '/favicon.ico',
        tag = null,
        requireInteraction = false,
        onClick = null,
        playSound = true,
        soundUrl = null,
    }) {
        if (!this.isSupported()) {
            console.warn('Notificaciones no soportadas');
            return null;
        }

        // Solicitar permiso si no lo tenemos
        if (!this.hasPermission()) {
            const granted = await this.requestPermission();
            if (!granted) {
                console.warn('Permiso de notificaciones denegado');
                return null;
            }
        }

        try {
            const notification = new Notification(title, {
                body,
                icon,
                tag,
                requireInteraction,
                badge: icon,
                silent: !playSound, // No usar sonido nativo del navegador
            });

            // Reproducir nuestro sonido personalizado
            if (playSound) {
                this.playSound(soundUrl);
            }

            // Evento al hacer click
            if (onClick) {
                notification.onclick = (event) => {
                    event.preventDefault();
                    window.focus();
                    onClick(event);
                    notification.close();
                };
            }

            // Auto-cerrar después de 10 segundos si no requiere interacción
            if (!requireInteraction) {
                setTimeout(() => notification.close(), 10000);
            }

            return notification;
        } catch (error) {
            console.error('Error al mostrar notificación:', error);
            return null;
        }
    }

    /**
     * Notificación específica para nuevo lead
     */
    async notifyNewLead(lead) {
        return this.show({
            title: '🎉 Nuevo Lead Recibido',
            body: `${lead.name || lead.phone}\n${lead.campaign?.name || 'Campaña'}`,
            tag: `lead-${lead.id}`,
            requireInteraction: false,
            playSound: true,
            onClick: () => {
                // Navegar a la página de leads si no estamos ahí
                if (!window.location.pathname.includes('/leads')) {
                    window.location.href = '/leads';
                }
            },
        });
    }

    /**
     * Notificación específica para lead actualizado
     */
    async notifyLeadUpdated(lead) {
        return this.show({
            title: '📝 Lead Actualizado',
            body: `${lead.name || lead.phone} - ${lead.status}`,
            tag: `lead-update-${lead.id}`,
            requireInteraction: false,
            playSound: false, // Sin sonido para actualizaciones
        });
    }
}

// Exportar instancia singleton
export const notifications = new NotificationManager();

// Helpers rápidos
export const requestNotificationPermission = () => notifications.requestPermission();
export const hasNotificationPermission = () => notifications.hasPermission();
export const notifyNewLead = (lead) => notifications.notifyNewLead(lead);
export const notifyLeadUpdated = (lead) => notifications.notifyLeadUpdated(lead);

