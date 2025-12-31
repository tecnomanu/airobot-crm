import { Button } from "@/Components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Switch } from "@/Components/ui/switch";
import { useForm } from "@inertiajs/react";
import { UserCheck } from "lucide-react";
import { useEffect } from "react";
import { toast } from "sonner";

export default function UserFormModal({
    open,
    onOpenChange,
    user = null,
    clients = [],
    roles = [],
    canViewAllClients = false,
}) {
    const isEditing = !!user;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: "",
        email: "",
        password: "",
        role: "user",
        is_seller: false,
        client_id: "",
    });

    // Load user data when editing
    useEffect(() => {
        if (user) {
            setData({
                name: user.name || "",
                email: user.email || "",
                password: "",
                role: user.role || "user",
                is_seller: user.is_seller || false,
                client_id: user.client_id || "",
            });
        } else {
            reset();
        }
    }, [user]);

    // Reset form when modal closes
    useEffect(() => {
        if (!open) {
            reset();
        }
    }, [open]);

    const handleSubmit = (e) => {
        e.preventDefault();

        if (isEditing) {
            put(route("users.update", user.id), {
                onSuccess: () => {
                    onOpenChange(false);
                    toast.success("Usuario actualizado exitosamente");
                },
                onError: () => {
                    toast.error("Error al actualizar el usuario");
                },
            });
        } else {
            post(route("users.store"), {
                onSuccess: () => {
                    onOpenChange(false);
                    toast.success("Usuario creado exitosamente");
                },
                onError: () => {
                    toast.error("Error al crear el usuario");
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? "Editar Usuario" : "Crear Nuevo Usuario"}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="name">Nombre *</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                        />
                        {errors.name && (
                            <p className="text-sm text-red-500">{errors.name}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">Email *</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData("email", e.target.value)}
                        />
                        {errors.email && (
                            <p className="text-sm text-red-500">{errors.email}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password">
                            {isEditing
                                ? "Contraseña (dejar vacío para no cambiar)"
                                : "Contraseña *"}
                        </Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData("password", e.target.value)}
                        />
                        {errors.password && (
                            <p className="text-sm text-red-500">{errors.password}</p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="role">Rol *</Label>
                            <Select
                                value={data.role}
                                onValueChange={(value) => setData("role", value)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {roles.map((role) => (
                                        <SelectItem key={role.value} value={role.value}>
                                            {role.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {canViewAllClients && (
                            <div className="space-y-2">
                                <Label htmlFor="client_id">Cliente</Label>
                                <Select
                                    value={data.client_id || "global"}
                                    onValueChange={(value) =>
                                        setData("client_id", value === "global" ? "" : value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Global (sin cliente)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="global">
                                            Global (sin cliente)
                                        </SelectItem>
                                        {clients.map((client) => (
                                            <SelectItem key={client.id} value={client.id}>
                                                {client.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                    </div>

                    <div className="flex items-center justify-between rounded-lg border p-4">
                        <div className="flex items-center gap-3">
                            <UserCheck className="h-5 w-5 text-green-600" />
                            <div>
                                <Label
                                    htmlFor="is_seller"
                                    className="text-sm font-medium"
                                >
                                    Es Vendedor
                                </Label>
                                <p className="text-xs text-muted-foreground">
                                    Puede recibir leads asignados en campañas
                                </p>
                            </div>
                        </div>
                        <Switch
                            id="is_seller"
                            checked={data.is_seller}
                            onCheckedChange={(checked) => setData("is_seller", checked)}
                        />
                    </div>

                    <div className="flex justify-end gap-3 pt-4 border-t">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing
                                ? isEditing
                                    ? "Actualizando..."
                                    : "Creando..."
                                : isEditing
                                ? "Actualizar Usuario"
                                : "Crear Usuario"}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

