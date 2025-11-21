/**
 * Utilidad de debug para Laravel Echo
 * Ayuda a diagnosticar problemas de conexión con Reverb
 */

export function debugEchoConnection() {
    if (!window.Echo) {
        console.error('❌ Echo no está inicializado');
        return;
    }

    const connector = window.Echo.connector;
    const pusher = connector?.pusher;

    if (!pusher) {
        console.error('❌ Pusher no está disponible');
        return;
    }

    console.group('🔌 Estado de Laravel Echo');

    // Estado de conexión
    console.log('Estado:', pusher.connection.state);
    console.log('Socket ID:', pusher.connection.socket_id);

    // Configuración
    console.log('Configuración:', {
        key: pusher.config.auth?.key || pusher.key,
        wsHost: pusher.config.wsHost,
        wsPort: pusher.config.wsPort,
        forceTLS: pusher.config.forceTLS,
    });

    // Canales suscritos
    const channels = Object.keys(pusher.channels.channels);
    console.log('Canales suscritos:', channels.length > 0 ? channels : 'Ninguno');

    console.groupEnd();

    // Eventos de conexión
    pusher.connection.bind('state_change', (states) => {
        console.log(`🔄 Conexión: ${states.previous} → ${states.current}`);
    });

    pusher.connection.bind('error', (err) => {
        console.error('❌ Error de conexión:', err);
    });

    pusher.connection.bind('connected', () => {
        console.log('✅ Conectado a Reverb');
    });

    return {
        state: pusher.connection.state,
        socketId: pusher.connection.socket_id,
        channels,
    };
}

// Auto-ejecutar en desarrollo
if (import.meta.env.DEV) {
    // Esperar a que Echo esté listo
    setTimeout(() => {
        debugEchoConnection();
    }, 1000);
}

