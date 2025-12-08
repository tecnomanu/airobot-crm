import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

/**
 * Hook para colaboración en tiempo real en Calculator
 * Maneja eventos broadcast de Reverb para sincronizar cambios entre usuarios
 */
export function useCalculatorCollaboration(calculatorId, onCellUpdate, onColumnResize, onRowResize, onNameUpdate, onCursorMove, currentUserId) {
    const [isConnected, setIsConnected] = useState(false);
    const [activeUsers, setActiveUsers] = useState(new Map());
    const channelRef = useRef(null);
    const eventQueueRef = useRef([]);
    const localVersionRef = useRef(0);
    const hasShownInitialToastRef = useRef(false);

    useEffect(() => {
        if (!calculatorId || !window.Echo) {
            return;
        }

        // Evitar reconexiones innecesarias
        if (channelRef.current) {
            return;
        }

        console.log(`🔌 Conectando al canal calculator.${calculatorId}`);

        // Suscribirse al canal privado
        const channel = window.Echo.private(`calculator.${calculatorId}`);
        channelRef.current = channel;

        // Evento: Conexión exitosa
        channel.subscription.bind('pusher:subscription_succeeded', () => {
            console.log('✅ Conectado al canal de colaboración');
            setIsConnected(true);
            
            // Solo mostrar toast en la primera conexión
            if (!hasShownInitialToastRef.current) {
                toast.success('Conectado - Modo colaborativo activo', {
                    duration: 3000,
                });
                hasShownInitialToastRef.current = true;
            }
        });

        // Evento: Error de suscripción
        channel.subscription.bind('pusher:subscription_error', (error) => {
            console.error('❌ Error al conectar al canal:', error);
            setIsConnected(false);
            
            if (!hasShownInitialToastRef.current) {
                toast.error('Error al conectar al modo colaborativo', {
                    duration: 3000,
                });
                hasShownInitialToastRef.current = true;
            }
        });

        // Evento: Celda actualizada
        channel.listen('.cell.updated', (event) => {
            console.log('📝 Celda actualizada:', event);

            // Verificar versión para evitar aplicar eventos antiguos
            if (event.version <= localVersionRef.current) {
                console.log('⚠️ Evento con versión antigua ignorado');
                return;
            }

            localVersionRef.current = event.version;

            // Aplicar cambio localmente
            onCellUpdate?.(event.cell_id, event.value, event.format, event.version);

            // Notificar al usuario (opcional - puedes comentarlo si es muy molesto)
            // toast.info(`${event.user_name} editó ${event.cell_id}`, {
            //     duration: 2000,
            // });
        });

        // Evento: Rango de celdas actualizado
        channel.listen('.cell-range.updated', (event) => {
            console.log('📝 Rango actualizado:', event);

            if (event.version <= localVersionRef.current) {
                return;
            }

            localVersionRef.current = event.version;

            // Aplicar cambios de múltiples celdas
            event.cells.forEach(cell => {
                onCellUpdate?.(cell.cellId, cell.value, cell.format, event.version);
            });

            // toast.info(`${event.user_name} editó ${event.cells.length} celdas`, {
            //     duration: 2000,
            // });
        });

        // Evento: Columna redimensionada
        channel.listen('.column.resized', (event) => {
            console.log('↔️ Columna redimensionada:', event);

            if (event.version <= localVersionRef.current) {
                return;
            }

            localVersionRef.current = event.version;
            onColumnResize?.(event.column, event.width, event.version);
        });

        // Evento: Fila redimensionada
        channel.listen('.row.resized', (event) => {
            console.log('↕️ Fila redimensionada:', event);

            if (event.version <= localVersionRef.current) {
                return;
            }

            localVersionRef.current = event.version;
            onRowResize?.(event.row, event.height, event.version);
        });

        // Evento: Nombre actualizado
        channel.listen('.name.updated', (event) => {
            console.log('✏️ Nombre actualizado:', event);

            if (event.version <= localVersionRef.current) {
                return;
            }

            localVersionRef.current = event.version;
            onNameUpdate?.(event.name, event.version);

            // toast.info(`${event.user_name} cambió el nombre`, {
            //     duration: 2000,
            // });
        });

        // Evento: Cursor movido (presencia)
        channel.listen('.cursor.moved', (event) => {

            // Actualizar mapa de usuarios activos
            setActiveUsers(prev => {
                const updated = new Map(prev);
                updated.set(event.user_id, {
                    userId: event.user_id,
                    userName: event.user_name,
                    userColor: event.user_color,
                    cellId: event.cell_id,
                    timestamp: Date.now(),
                });
                return updated;
            });

            onCursorMove?.(event.user_id, event.cell_id, event.user_name, event.user_color);
        });

        // Cleanup al desmontar
        return () => {
            console.log('🔌 Desconectando del canal de colaboración');
            if (channelRef.current) {
                channelRef.current.stopListening('.cell.updated');
                channelRef.current.stopListening('.cell-range.updated');
                channelRef.current.stopListening('.column.resized');
                channelRef.current.stopListening('.row.resized');
                channelRef.current.stopListening('.name.updated');
                channelRef.current.stopListening('.cursor.moved');
                window.Echo.leave(`calculator.${calculatorId}`);
                channelRef.current = null;
            }
            setIsConnected(false);
        };
    }, [calculatorId, currentUserId]); // Eliminar callbacks de las dependencias

    // Limpiar usuarios inactivos cada 5 segundos
    useEffect(() => {
        const interval = setInterval(() => {
            const now = Date.now();
            setActiveUsers(prev => {
                const updated = new Map(prev);
                for (const [userId, user] of updated) {
                    if (now - user.timestamp > 10000) { // 10 segundos sin actividad
                        updated.delete(userId);
                    }
                }
                return updated;
            });
        }, 5000);

        return () => clearInterval(interval);
    }, []);

    /**
     * Actualizar versión local (llamar después de emitir cambios propios)
     */
    const updateLocalVersion = (version) => {
        localVersionRef.current = version;
    };

    return {
        isConnected,
        activeUsers: Array.from(activeUsers.values()),
        updateLocalVersion,
    };
}

