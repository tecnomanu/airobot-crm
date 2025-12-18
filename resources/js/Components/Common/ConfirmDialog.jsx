import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";

/**
 * Diálogo de confirmación reutilizable
 * 
 * @param {boolean} open - Estado del diálogo
 * @param {function} onOpenChange - Callback para cambiar estado
 * @param {function} onConfirm - Callback cuando se confirma
 * @param {string} title - Título del diálogo
 * @param {string} description - Descripción/mensaje
 * @param {string} confirmText - Texto del botón confirmar (default: "Confirmar")
 * @param {string} cancelText - Texto del botón cancelar (default: "Cancelar")
 * @param {string} variant - Variante del botón: "default" | "destructive" (default: "default")
 */
export default function ConfirmDialog({
    open,
    onOpenChange,
    onConfirm,
    title = "¿Estás seguro?",
    description = "Esta acción no se puede deshacer.",
    confirmText = "Confirmar",
    cancelText = "Cancelar",
    variant = "default",
}) {
    const handleConfirm = () => {
        onConfirm();
        onOpenChange(false);
    };

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>{description}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>{cancelText}</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleConfirm}
                        className={
                            variant === "destructive"
                                ? "bg-red-600 hover:bg-red-700 focus:ring-red-600"
                                : ""
                        }
                    >
                        {confirmText}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

