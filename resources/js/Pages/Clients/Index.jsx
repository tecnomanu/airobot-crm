import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import { Button } from "@/Components/ui/button";
import { DataTable } from "@/Components/ui/data-table";
import DataTableFilters from "@/Components/ui/data-table-filters";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router, useForm } from "@inertiajs/react";
import { Plus } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { getClientColumns } from "./columns";

export default function ClientsIndex({ clients, filters }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingClient, setEditingClient] = useState(null);
    const [searchTerm, setSearchTerm] = useState(filters.search || "");
    const [deleteDialog, setDeleteDialog] = useState({
        open: false,
        id: null,
        name: "",
    });
    const [statusDialog, setStatusDialog] = useState({
        open: false,
        client: null,
    });

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: "",
        email: "",
        phone: "",
        company: "",
        status: "active",
        notes: "",
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("clients.index"),
            { ...filters, search: searchTerm },
            { preserveState: true }
        );
    };

    const handleFilterChange = (name, value) => {
        router.get(
            route("clients.index"),
            { ...filters, [name]: value },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        setSearchTerm("");
        router.get(route("clients.index"), {}, { preserveState: true });
    };

    const handleCreate = () => {
        reset();
        setEditingClient(null);
        setIsModalOpen(true);
    };

    const handleEdit = (client) => {
        setData({
            name: client.name || "",
            email: client.email || "",
            phone: client.phone || "",
            company: client.company || "",
            status: client.status || "active",
            notes: client.notes || "",
        });
        setEditingClient(client);
        setIsModalOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (editingClient) {
            // Update existing client
            put(route("clients.update", editingClient.id), {
                onSuccess: () => {
                    setIsModalOpen(false);
                    reset();
                    setEditingClient(null);
                    toast.success("Client actualizado exitosamente");
                },
                onError: () => {
                    toast.error("Error al actualizar el cliente");
                },
            });
        } else {
            // Create new client
            post(route("clients.store"), {
                onSuccess: () => {
                    setIsModalOpen(false);
                    reset();
                    toast.success("Cliente creado exitosamente");
                },
                onError: () => {
                    toast.error("Error al crear el cliente");
                },
            });
        }
    };

    const handleModalClose = (open) => {
        setIsModalOpen(open);
        if (!open) {
            reset();
            setEditingClient(null);
        }
    };

    const handleDelete = (client) => {
        setDeleteDialog({
            open: true,
            id: client.id,
            name: client.name,
        });
    };

    const confirmDelete = () => {
        router.delete(route("clients.destroy", deleteDialog.id), {
            onSuccess: () => {
                toast.success("Cliente eliminado exitosamente");
            },
            onError: () => {
                toast.error("Error al eliminar el cliente");
            },
        });
        setDeleteDialog({ open: false, id: null, name: "" });
    };

    const handleToggleStatus = (client) => {
        setStatusDialog({ open: true, client });
    };

    const confirmToggleStatus = () => {
        const client = statusDialog.client;
        const newStatus = client.status === "active" ? "inactive" : "active";

        router.put(
            route("clients.update", client.id),
            { ...client, status: newStatus },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(
                        newStatus === "active"
                            ? "Cliente activado exitosamente"
                            : "Cliente desactivado exitosamente"
                    );
                },
                onError: () => {
                    toast.error("Error al cambiar el estado del cliente");
                },
            }
        );
        setStatusDialog({ open: false, client: null });
    };

    return (
        <AppLayout
            header={{
                title: "Clientes",
                subtitle: "Gestión de clientes y empresas",
                actions: (
                    <Button
                        size="sm"
                        className="h-8 text-xs px-2"
                        onClick={handleCreate}
                    >
                        <Plus className="h-3.5 w-3.5 mr-1.5" />
                        Nuevo Cliente
                    </Button>
                ),
            }}
        >
            <Head title="Clientes" />

            {/* Main Card Container */}
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
                {/* Header Section with padding */}
                <div className="p-6">
                    <DataTable
                        columns={getClientColumns(handleDelete, handleEdit, handleToggleStatus)}
                        data={clients.data}
                        pagination={clients}
                        actions={
                            <DataTableFilters
                                searchPlaceholder="Buscar cliente..."
                                filters={[
                                    {
                                        key: "status",
                                        label: "Estado",
                                        options: [
                                            {
                                                value: "active",
                                                label: "Activo",
                                            },
                                            {
                                                value: "inactive",
                                                label: "Inactivo",
                                            },
                                        ],
                                    },
                                ]}
                                values={{
                                    search: filters.search || "",
                                    status: filters.status || "all",
                                }}
                                onChange={(values) => {
                                    router.get(
                                        route("clients.index"),
                                        { ...route().params, ...values },
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        }
                                    );
                                }}
                                onClear={() => {
                                    router.get(
                                        route("clients.index"),
                                        {},
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        }
                                    );
                                }}
                            />
                        }
                    />
                </div>
            </div>

            {/* Client Modal */}
            <Dialog open={isModalOpen} onOpenChange={handleModalClose}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingClient
                                ? "Editar Cliente"
                                : "Crear Nuevo Cliente"}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData("name", e.target.value)
                                    }
                                />
                                {errors.name && (
                                    <p className="text-sm text-red-500">
                                        {errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="company">Empresa</Label>
                                <Input
                                    id="company"
                                    value={data.company}
                                    onChange={(e) =>
                                        setData("company", e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData("email", e.target.value)
                                    }
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="phone">Teléfono</Label>
                                <Input
                                    id="phone"
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData("phone", e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="status">Estado</Label>
                            <div className="flex items-center gap-3 pt-1">
                                <Switch
                                    id="status"
                                    checked={data.status === "active"}
                                    onCheckedChange={(checked) =>
                                        setData("status", checked ? "active" : "inactive")
                                    }
                                    className="data-[state=checked]:bg-green-600"
                                />
                                <span className={`text-sm font-medium ${
                                    data.status === "active" ? "text-green-600" : "text-gray-500"
                                }`}>
                                    {data.status === "active" ? "Activo" : "Inactivo"}
                                </span>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="notes">Notas</Label>
                            <textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) =>
                                    setData("notes", e.target.value)
                                }
                                rows={3}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            />
                        </div>

                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => handleModalClose(false)}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing
                                    ? editingClient
                                        ? "Actualizando..."
                                        : "Creando..."
                                    : editingClient
                                    ? "Actualizar Cliente"
                                    : "Crear Cliente"}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Confirm Delete Dialog */}
            <ConfirmDialog
                open={deleteDialog.open}
                onOpenChange={(open) =>
                    setDeleteDialog({ ...deleteDialog, open })
                }
                onConfirm={confirmDelete}
                title="¿Eliminar cliente?"
                description={`¿Estás seguro de eliminar el cliente "${deleteDialog.name}"? Se eliminarán todas sus campañas, leads y registros asociados. Esta acción no se puede deshacer.`}
                confirmText="Eliminar"
                cancelText="Cancelar"
                variant="destructive"
            />

            {/* Confirm Status Toggle Dialog */}
            <ConfirmDialog
                open={statusDialog.open}
                onOpenChange={(open) =>
                    setStatusDialog({ ...statusDialog, open })
                }
                onConfirm={confirmToggleStatus}
                title={
                    statusDialog.client?.status === "active"
                        ? "¿Desactivar cliente?"
                        : "¿Activar cliente?"
                }
                description={
                    statusDialog.client?.status === "active"
                        ? `¿Estás seguro de desactivar el cliente "${statusDialog.client?.name}"? Sus campañas y leads seguirán disponibles pero el cliente no estará activo.`
                        : `¿Estás seguro de activar el cliente "${statusDialog.client?.name}"?`
                }
                confirmText={statusDialog.client?.status === "active" ? "Desactivar" : "Activar"}
                cancelText="Cancelar"
                variant={statusDialog.client?.status === "active" ? "destructive" : "default"}
            />
        </AppLayout>
    );
}
