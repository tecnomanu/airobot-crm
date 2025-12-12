import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import { Button } from "@/components/ui/button";
import { DataTable } from "@/components/ui/data-table";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router, useForm } from "@inertiajs/react";
import { Plus, Search, X } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { getClientColumns } from "./columns";

export default function ClientsIndex({ clients, filters }) {
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState(filters.search || "");
    const [deleteDialog, setDeleteDialog] = useState({
        open: false,
        id: null,
        name: "",
    });

    const { data, setData, post, processing, errors, reset } = useForm({
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

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("clients.store"), {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
                toast.success("Cliente creado exitosamente");
            },
            onError: () => {
                toast.error("Error al crear el cliente");
            },
        });
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

    return (
        <AppLayout
            header={{
                title: "Clientes",
                subtitle: "Gestión de clientes y empresas",
                actions: (
                    <Button
                        size="sm"
                        className="h-8 text-xs px-2"
                        onClick={() => setIsCreateModalOpen(true)}
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
                <div className="p-6 space-y-4">
                    {/* Filters Row */}
                    <div className="flex flex-wrap items-center gap-3">
                        <form
                            onSubmit={handleSearch}
                            className="flex-1 max-w-md relative"
                        >
                            <Input
                                placeholder="Buscar cliente..."
                                value={searchTerm}
                                onChange={(e) =>
                                    setSearchTerm(e.target.value)
                                }
                                className="pl-4 pr-10 py-2.5 bg-indigo-50 border-0 rounded-lg"
                            />
                            <Button
                                type="submit"
                                size="icon"
                                variant="ghost"
                                className="absolute right-1 top-1/2 -translate-y-1/2 h-8 w-8"
                            >
                                <Search className="h-4 w-4 text-gray-400" />
                            </Button>
                        </form>

                        <Select
                            value={filters.status || "all"}
                            onValueChange={(value) =>
                                handleFilterChange(
                                    "status",
                                    value === "all" ? "" : value
                                )
                            }
                        >
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Todos los estados" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Todos los estados
                                </SelectItem>
                                <SelectItem value="active">
                                    Activo
                                </SelectItem>
                                <SelectItem value="inactive">
                                    Inactivo
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Button
                            variant="outline"
                            onClick={handleClearFilters}
                            size="sm"
                        >
                            <X className="mr-2 h-4 w-4" />
                            Limpiar
                        </Button>
                    </div>
                </div>

                {/* Table with padding */}
                <div className="px-6 pb-6">
                    <DataTable
                        columns={getClientColumns(handleDelete)}
                        data={clients.data}
                        filterColumn="name"
                    />
                </div>
            </div>

            {/* Create Client Modal */}
            <Dialog
                open={isCreateModalOpen}
                onOpenChange={setIsCreateModalOpen}
            >
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Crear Nuevo Cliente</DialogTitle>
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
                            <Select
                                value={data.status}
                                onValueChange={(value) =>
                                    setData("status", value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">
                                        Activo
                                    </SelectItem>
                                    <SelectItem value="inactive">
                                        Inactivo
                                    </SelectItem>
                                </SelectContent>
                            </Select>
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
                                onClick={() => setIsCreateModalOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? "Creando..." : "Crear Cliente"}
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
        </AppLayout>
    );
}
