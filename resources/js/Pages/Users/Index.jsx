import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import UserFormModal from "@/Components/Users/UserFormModal";
import { Button } from "@/Components/ui/button";
import { DataTable } from "@/Components/ui/data-table";
import DataTableFilters from "@/Components/ui/data-table-filters";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router } from "@inertiajs/react";
import { Plus } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { getUserColumns } from "./columns";

export default function UsersIndex({ users, filters, clients, roles, can }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState(null);
    const [deleteDialog, setDeleteDialog] = useState({
        open: false,
        id: null,
        name: "",
    });
    const [toggleDialog, setToggleDialog] = useState({
        open: false,
        user: null,
        newStatus: null,
    });

    const handleFilterChange = (name, value) => {
        const newFilters = { ...filters, [name]: value };
        // Remove empty values
        Object.keys(newFilters).forEach((key) => {
            if (newFilters[key] === "" || newFilters[key] === null) {
                delete newFilters[key];
            }
        });
        router.get(route("users.index"), newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleCreate = () => {
        setEditingUser(null);
        setIsModalOpen(true);
    };

    const handleEdit = (user) => {
        setEditingUser(user);
        setIsModalOpen(true);
    };

    const handleModalClose = (open) => {
        setIsModalOpen(open);
        if (!open) {
            setEditingUser(null);
        }
    };

    const handleDelete = (user) => {
        setDeleteDialog({
            open: true,
            id: user.id,
            name: user.name,
        });
    };

    const confirmDelete = () => {
        router.delete(route("users.destroy", deleteDialog.id), {
            onSuccess: () => {
                toast.success("Usuario eliminado exitosamente");
            },
            onError: () => {
                toast.error("Error al eliminar el usuario");
            },
        });
        setDeleteDialog({ open: false, id: null, name: "" });
    };

    const handleToggleSeller = (user) => {
        router.patch(
            route("users.toggle-seller", user.id),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(
                        user.is_seller
                            ? "Usuario ya no es vendedor"
                            : "Usuario ahora es vendedor"
                    );
                },
                onError: () => {
                    toast.error("No tienes permisos para realizar esta acción");
                },
            }
        );
    };

    const handleToggleStatus = (user) => {
        const newStatus = user.status === "active" ? "inactive" : "active";
        setToggleDialog({
            open: true,
            user: user,
            newStatus: newStatus,
        });
    };

    const confirmToggleStatus = () => {
        router.patch(
            route("users.toggle-status", toggleDialog.user.id),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    const action =
                        toggleDialog.newStatus === "active"
                            ? "activado"
                            : "desactivado";
                    toast.success(`Usuario ${action} exitosamente`);
                },
                onError: () => {
                    toast.error("No tienes permisos para realizar esta acción");
                },
            }
        );
        setToggleDialog({ open: false, user: null, newStatus: null });
    };

    // Build filter options
    const filterOptions = [
        {
            name: "search",
            type: "search",
            placeholder: "Buscar usuario...",
        },
        {
            name: "is_seller",
            type: "select",
            placeholder: "Vendedor",
            allLabel: "Todos",
            className: "w-[140px]",
            options: [
                { value: "1", label: "Sí" },
                { value: "0", label: "No" },
            ],
        },
        {
            name: "role",
            type: "select",
            placeholder: "Rol",
            allLabel: "Todos los roles",
            className: "w-[160px]",
            options: roles.map((r) => ({
                value: r.value,
                label: r.label,
            })),
        },
    ];

    // Add client filter only for admins
    if (can.viewAllClients && clients.length > 0) {
        filterOptions.push({
            name: "client_id",
            type: "select",
            placeholder: "Cliente",
            allLabel: "Todos los clientes",
            className: "w-[180px]",
            options: [
                { value: "global", label: "Global (sin cliente)" },
                ...clients.map((c) => ({
                    value: c.id,
                    label: c.name,
                })),
            ],
        });
    }

    return (
        <AppLayout
            header={{
                title: "Usuarios",
                subtitle: "Gestión de usuarios y vendedores",
                actions: can.create && (
                    <Button
                        size="sm"
                        className="h-8 text-xs px-2"
                        onClick={handleCreate}
                    >
                        <Plus className="h-3.5 w-3.5 mr-1.5" />
                        Nuevo Usuario
                    </Button>
                ),
            }}
        >
            <Head title="Usuarios" />

            <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div className="p-6">
                    <DataTable
                        columns={getUserColumns(
                            handleDelete,
                            handleEdit,
                            handleToggleSeller,
                            handleToggleStatus,
                            can.create
                        )}
                        data={users.data}
                        pagination={users}
                        actions={
                            <DataTableFilters
                                filters={filterOptions}
                                values={{
                                    search: filters.search || "",
                                    is_seller: filters.is_seller || "all",
                                    role: filters.role || "all",
                                    client_id: filters.client_id || "all",
                                }}
                                onChange={handleFilterChange}
                                onClear={() => {
                                    router.get(
                                        route("users.index"),
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

            {/* User Form Modal */}
            <UserFormModal
                open={isModalOpen}
                onOpenChange={handleModalClose}
                user={editingUser}
                clients={clients}
                roles={roles}
                canViewAllClients={can.viewAllClients}
            />

            {/* Confirm Delete Dialog */}
            <ConfirmDialog
                open={deleteDialog.open}
                onOpenChange={(open) =>
                    setDeleteDialog({ ...deleteDialog, open })
                }
                onConfirm={confirmDelete}
                title="¿Eliminar usuario?"
                description={`¿Estás seguro de eliminar el usuario "${deleteDialog.name}"? Esta acción no se puede deshacer.`}
                confirmText="Eliminar"
                cancelText="Cancelar"
                variant="destructive"
            />

            {/* Confirm Toggle Status Dialog */}
            <ConfirmDialog
                open={toggleDialog.open}
                onOpenChange={(open) =>
                    setToggleDialog({ ...toggleDialog, open })
                }
                onConfirm={confirmToggleStatus}
                title={
                    toggleDialog.newStatus === "active"
                        ? "¿Activar usuario?"
                        : "¿Desactivar usuario?"
                }
                description={
                    toggleDialog.newStatus === "active"
                        ? `¿Estás seguro de activar el usuario "${toggleDialog.user?.name}"? Podrá acceder al sistema nuevamente.`
                        : `¿Estás seguro de desactivar el usuario "${toggleDialog.user?.name}"? No podrá acceder al sistema hasta que lo reactives.`
                }
                confirmText={
                    toggleDialog.newStatus === "active"
                        ? "Activar"
                        : "Desactivar"
                }
                cancelText="Cancelar"
            />
        </AppLayout>
    );
}
