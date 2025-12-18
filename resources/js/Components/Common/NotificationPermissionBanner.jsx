import { Button } from "@/Components/ui/button";
import { notifications } from "@/lib/notifications";
import { Bell, X } from "lucide-react";
import { useEffect, useState } from "react";

/**
 * Banner para solicitar permiso de notificaciones
 * Se muestra solo si el usuario no ha dado/denegado permiso aún
 */
export default function NotificationPermissionBanner() {
    const [show, setShow] = useState(false);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        // Solo mostrar si las notificaciones están soportadas y no hay permiso
        if (
            notifications.isSupported() &&
            Notification.permission === "default"
        ) {
            // Verificar si el usuario ya cerró el banner previamente
            const dismissed = localStorage.getItem(
                "notification-banner-dismissed"
            );
            if (!dismissed) {
                setShow(true);
            }
        }
    }, []);

    const handleEnable = async () => {
        setLoading(true);
        const granted = await notifications.requestPermission();

        if (granted) {
            setShow(false);
            // Mostrar notificación de bienvenida
            notifications.show({
                title: "✅ Notificaciones Activadas",
                body: "Te notificaremos cuando lleguen nuevos leads",
                playSound: true,
            });
        }

        setLoading(false);
    };

    const handleDismiss = () => {
        setShow(false);
        // Recordar que el usuario cerró el banner (por 7 días)
        const expiryDate = new Date();
        expiryDate.setDate(expiryDate.getDate() + 7);
        localStorage.setItem(
            "notification-banner-dismissed",
            expiryDate.toISOString()
        );
    };

    if (!show) return null;

    return (
        <div className="fixed bottom-4 right-4 z-50 max-w-md animate-in slide-in-from-bottom-5">
            <div className="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg shadow-2xl p-4">
                <div className="flex items-start gap-3">
                    <div className="flex-shrink-0 mt-0.5">
                        <Bell className="h-5 w-5" />
                    </div>

                    <div className="flex-1 min-w-0">
                        <h3 className="font-semibold text-sm mb-1">
                            Activar Notificaciones
                        </h3>
                        <p className="text-sm text-blue-50 mb-3">
                            Recibe alertas instantáneas cuando lleguen nuevos
                            leads
                        </p>

                        <div className="flex gap-2">
                            <Button
                                onClick={handleEnable}
                                disabled={loading}
                                size="sm"
                                className="bg-white text-blue-600 hover:bg-blue-50 font-medium"
                            >
                                {loading ? "Habilitando..." : "Activar"}
                            </Button>
                            <Button
                                onClick={handleDismiss}
                                size="sm"
                                variant="ghost"
                                className="text-white hover:bg-white/20"
                            >
                                Ahora no
                            </Button>
                        </div>
                    </div>

                    <button
                        onClick={handleDismiss}
                        className="flex-shrink-0 text-white/80 hover:text-white transition-colors"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    );
}
