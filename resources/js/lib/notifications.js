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

    /**
     * Notificación específica para lead eliminado
     */
    async notifyLeadDeleted(lead) {
        return this.show({
            title: '🗑️ Lead Eliminado',
            body: `${lead.name || lead.phone} ha sido eliminado`,
            tag: `lead-delete-${lead.id}`,
            requireInteraction: false,
            playSound: false,
        });
    }

    /**
     * Notificación específica para cambio de intención
     */
    async notifyLeadIntention(lead, intention) {
        const intentionLabels = {
            interested: '✅ Interesado',
            not_interested: '❌ No Interesado',
            undecided: '🤔 Indeciso',
        };

        const label = intentionLabels[intention] || intention;

        return this.show({
            title: '💭 Intención Detectada',
            body: `${lead.name || lead.phone}\n${label}`,
            tag: `lead-intention-${lead.id}`,
            requireInteraction: false,
            playSound: true, // Sonido para intenciones importantes
            onClick: () => {
                if (!window.location.pathname.includes('/leads-intencion')) {
                    window.location.href = '/leads-intencion';
                }
            },
        });
    }

    /**
     * Notificación específica para llamada completada
     */
    async notifyCallCompleted(call) {
        const durationMin = Math.floor(call.duration / 60);
        const statusLabels = {
            completed: '✅ Completada',
            no_answer: '📵 Sin respuesta',
            hung_up: '📞 Colgó',
            failed: '❌ Fallida',
            busy: '🔴 Ocupado',
            voicemail: '📧 Buzón',
        };

        const statusLabel = statusLabels[call.status] || call.status;

        return this.show({
            title: '📞 Nueva Llamada',
            body: `${call.phone}\n${statusLabel} - ${durationMin} min`,
            tag: `call-${call.id}`,
            requireInteraction: false,
            playSound: call.status === 'completed', // Sonido solo para completadas
            onClick: () => {
                if (!window.location.pathname.includes('/call-history')) {
                    window.location.href = '/call-history';
                }
            },
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
export const notifyLeadDeleted = (lead) => notifications.notifyLeadDeleted(lead);
export const notifyLeadIntention = (lead, intention) => notifications.notifyLeadIntention(lead, intention);
export const notifyCallCompleted = (call) => notifications.notifyCallCompleted(call);

