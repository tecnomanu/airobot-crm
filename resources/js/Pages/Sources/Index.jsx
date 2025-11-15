import { useState, useEffect } from "react";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router, useForm } from "@inertiajs/react";
import { Plus, Search, X } from "lucide-react";
import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import { toast } from "sonner";
import { DataTable } from "@/components/ui/data-table";
import { getSourceColumns } from "./columns";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/components/ui/dialog";
import {
    Card,
    CardContent,
} from "@/components/ui/card";
import SourceFormWhatsApp from "@/Components/Sources/SourceFormWhatsApp";
import SourceFormWebhook from "@/Components/Sources/SourceFormWebhook";

export default function SourcesIndex({ sources, clients, filters, presetType = null }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingSource, setEditingSource] = useState(null);
    const [searchTerm, setSearchTerm] = useState(filters.search || "");
    const [deleteDialog, setDeleteDialog] = useState({ open: false, id: null, name: "" });

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: "",
        type: presetType || "",
        status: "active",
        client_id: "",
        config: {},
    });

    // Resetear formulario cuando se abre/cierra modal
    useEffect(() => {
        if (!isModalOpen) {
            setEditingSource(null);
            reset();
            if (presetType) {
                setData("type", presetType);
            }
        }
    }, [isModalOpen]);

    // Cargar datos al editar
    useEffect(() => {
        if (editingSource) {
            setData({
                name: editingSource.name || "",
                type: editingSource.type || "",
                status: editingSource.status || "active",
                client_id: editingSource.client_id?.toString() || "",
                config: editingSource.config || {},
            });
        }
    }, [editingSource]);

    // Inicializar tipo si es preset
    useEffect(() => {
        if (presetType && !editingSource) {
            setData("type", presetType);
        }
    }, [presetType, editingSource]);

    const handleFilterChange = (name, value) => {
        router.get(
            route("sources.index"),
            { ...filters, [name]: value },
            { preserveState: true }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("sources.index"),
            { ...filters, search: searchTerm },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        setSearchTerm("");
        router.get(route("sources.index"), {}, { preserveState: true });
    };

    const handleCreate = () => {
        setEditingSource(null);
        setIsModalOpen(true);
    };

    const handleEdit = (source) => {
        setEditingSource(source);
        setIsModalOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (editingSource) {
            put(route("sources.update", editingSource.id), {
                onSuccess: () => {
                    setIsModalOpen(false);
                    toast.success("Fuente actualizada exitosamente");
                },
                onError: () => {
                    toast.error("Error al actualizar la fuente");
                },
            });
        } else {
            post(route("sources.store"), {
                onSuccess: () => {
                    setIsModalOpen(false);
                    toast.success("Fuente creada exitosamente");
                },
                onError: () => {
                    toast.error("Error al crear la fuente");
                },
            });
        }
    };

    const handleDelete = (source) => {
        setDeleteDialog({
            open: true,
            id: source.id,
            name: source.name,
        });
    };

    const confirmDelete = () => {
        router.delete(route("sources.destroy", deleteDialog.id), {
            onSuccess: () => {
                toast.success("Fuente eliminada exitosamente");
            },
            onError: () => {
                toast.error("Error al eliminar la fuente");
            },
        });
        setDeleteDialog({ open: false, id: null, name: "" });
    };

    const getDialogTitle = () => {
        if (editingSource) return "Editar Fuente";
        if (presetType === "whatsapp") return "Crear Fuente de WhatsApp";
        if (presetType === "webhook") return "Crear Fuente de Webhook";
        return "Crear Nueva Fuente";
    };

    const getDialogDescription = () => {
        if (editingSource) return "Modifica los datos de esta fuente existente.";
        if (presetType === "whatsapp") 
            return "Configura una nueva fuente de WhatsApp para enviar mensajes a tus leads.";
        if (presetType === "webhook") 
            return "Configura una nueva fuente de webhook para integrar con sistemas externos.";
        return "Completa los datos para crear una nueva fuente reutilizable.";
    };

    return (
        <AppLayout>
            <Head title="Fuentes" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Fuentes</h1>
                        <p className="text-muted-foreground">
                            Gestión de fuentes de captura de leads
                        </p>
                    </div>
                    <Button onClick={handleCreate}>
                        <Plus className="mr-2 h-4 w-4" />
                        Nueva Fuente
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="grid gap-4 md:grid-cols-4">
                            <form onSubmit={handleSearch} className="flex gap-2">
                                <Input
                                    placeholder="Buscar fuente..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                                <Button type="submit" size="icon" variant="outline">
                                    <Search className="h-4 w-4" />
                                </Button>
                            </form>

                            <Select
                                value={filters.type || "all"}
                                onValueChange={(value) =>
                                    handleFilterChange("type", value === "all" ? "" : value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los tipos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los tipos</SelectItem>
                                    <SelectItem value="whatsapp">WhatsApp</SelectItem>
                                    <SelectItem value="webhook">Webhook</SelectItem>
                                    <SelectItem value="meta_whatsapp">WhatsApp Business</SelectItem>
                                    <SelectItem value="facebook_lead_ads">Facebook Lead Ads</SelectItem>
                                    <SelectItem value="google_ads">Google Ads</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select
                                value={filters.status || "all"}
                                onValueChange={(value) =>
                                    handleFilterChange("status", value === "all" ? "" : value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="active">Activo</SelectItem>
                                    <SelectItem value="inactive">Inactivo</SelectItem>
                                    <SelectItem value="error">Error</SelectItem>
                                    <SelectItem value="pending_setup">Pendiente configuración</SelectItem>
                                </SelectContent>
                            </Select>

                            <Button
                                variant="outline"
                                onClick={handleClearFilters}
                                className="w-full"
                            >
                                <X className="mr-2 h-4 w-4" />
                                Limpiar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <DataTable
                    columns={getSourceColumns(handleEdit, handleDelete)}
                    data={sources.data || sources}
                    filterColumn="name"
                />
            </div>

            {/* Create/Edit Source Modal */}
            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{getDialogTitle()}</DialogTitle>
                        <DialogDescription>{getDialogDescription()}</DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Selector de tipo (solo si no es preset) */}
                        {!presetType && (
                            <div className="space-y-2">
                                <Label htmlFor="type">
                                    Tipo de Fuente <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={data.type}
                                    onValueChange={(value) => setData("type", value)}
                                    disabled={!!editingSource}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="whatsapp">WhatsApp (Evolution API)</SelectItem>
                                        <SelectItem value="webhook">Webhook HTTP</SelectItem>
                                        <SelectItem value="meta_whatsapp">WhatsApp Business (Meta)</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.type && (
                                    <p className="text-sm text-red-500">{errors.type}</p>
                                )}
                            </div>
                        )}

                        {/* Formulario específico según tipo */}
                        {data.type === "whatsapp" && (
                            <SourceFormWhatsApp
                                data={data}
                                setData={setData}
                                errors={errors}
                                clients={clients}
                            />
                        )}

                        {data.type === "webhook" && (
                            <SourceFormWebhook
                                data={data}
                                setData={setData}
                                errors={errors}
                                clients={clients}
                            />
                        )}

                        {/* Mensaje si no hay tipo seleccionado */}
                        {!data.type && (
                            <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                                <p className="font-medium">Selecciona un tipo de fuente</p>
                                <p className="text-xs mt-1">
                                    Primero selecciona el tipo de fuente que deseas configurar.
                                </p>
                            </div>
                        )}

                        {/* Tipos no implementados */}
                        {data.type && !["whatsapp", "webhook"].includes(data.type) && (
                            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                <p className="font-medium">Configuración en desarrollo</p>
                                <p className="text-xs mt-1">
                                    La configuración para este tipo de fuente estará disponible próximamente.
                                </p>
                            </div>
                        )}

                        <DialogFooter className="gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsModalOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing || !data.type}>
                                {processing 
                                    ? (editingSource ? "Actualizando..." : "Creando...") 
                                    : (editingSource ? "Actualizar Fuente" : "Crear Fuente")
                                }
                            </Button>
                        </DialogFooter>
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
                title="¿Eliminar fuente?"
                description={`¿Estás seguro de eliminar la fuente "${deleteDialog.name}"? Las campañas asociadas perderán esta fuente. Esta acción no se puede deshacer.`}
                confirmText="Eliminar"
                cancelText="Cancelar"
                variant="destructive"
            />
        </AppLayout>
    );
}
