import { useState } from "react";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router, useForm } from "@inertiajs/react";
import axios from "axios";
import { Plus, Search, X, Calculator } from "lucide-react";
import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import { toast } from "sonner";
import { DataTable } from "@/components/ui/data-table";
import { getCalculatorColumns } from "./columns";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Card,
    CardContent,
} from "@/components/ui/card";

export default function CalculatorList({ calculators, filters = {} }) {
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState(filters.search || "");
    const [deleteDialog, setDeleteDialog] = useState({
        open: false,
        id: null,
        name: "",
    });

    const { data, setData, post, processing, errors, reset } = useForm({
        name: "Hoja sin título",
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("calculator.index"),
            { search: searchTerm },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        setSearchTerm("");
        router.get(route("calculator.index"), {}, { preserveState: true });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        try {
            const response = await axios.post(route("api.admin.calculator.store"), data);
            
            if (response.data.success && response.data.data?.id) {
                setIsCreateModalOpen(false);
                reset();
                toast.success("Calculator creado exitosamente");
                router.visit(route("calculator.show", response.data.data.id));
            } else {
                toast.error("Error al crear el calculator");
            }
        } catch (error) {
            console.error("Error:", error);
            toast.error("Error al crear el calculator");
        }
    };

    const handleDelete = (calculator) => {
        setDeleteDialog({
            open: true,
            id: calculator.id,
            name: calculator.name,
        });
    };

    const confirmDelete = async () => {
        try {
            await axios.delete(route("api.admin.calculator.destroy", deleteDialog.id));

            toast.success("Calculator eliminado exitosamente");
            router.reload();
        } catch (error) {
            console.error("Error al eliminar calculator:", error);
            toast.error("Error al eliminar calculator");
        }

        setDeleteDialog({ open: false, id: null, name: "" });
    };

    const handleNewCalculator = async () => {
        try {
            const response = await axios.post(route("api.admin.calculator.store"), {
                name: "Hoja sin título",
            });

            if (response.data.success && response.data.data?.id) {
                router.visit(route("calculator.show", response.data.data.id));
            }
        } catch (error) {
            console.error("Error al crear nuevo calculator:", error);
            toast.error("Error al crear nuevo calculator");
        }
    };

    return (
        <AppLayout
            header={{
                title: "Calculators",
                subtitle: "Gestión de hojas de cálculo inteligentes",
                actions: [
                    <Button
                        key="create"
                        size="sm"
                        className="h-8 text-xs px-3"
                        onClick={handleNewCalculator}
                    >
                        <Plus className="h-3.5 w-3.5 mr-1.5" />
                        Nuevo Calculator
                    </Button>,
                ],
            }}
        >
            <Head title="Calculators" />

            <div className="space-y-6">
                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex gap-4">
                            <form
                                onSubmit={handleSearch}
                                className="flex gap-2 flex-1"
                            >
                                <Input
                                    placeholder="Buscar por nombre..."
                                    value={searchTerm}
                                    onChange={(e) =>
                                        setSearchTerm(e.target.value)
                                    }
                                    className="flex-1"
                                />
                                <Button
                                    type="submit"
                                    size="icon"
                                    variant="outline"
                                >
                                    <Search className="h-4 w-4" />
                                </Button>
                            </form>

                            <Button
                                variant="outline"
                                onClick={handleClearFilters}
                            >
                                <X className="mr-2 h-4 w-4" />
                                Limpiar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                {calculators && calculators.length > 0 ? (
                    <DataTable
                        columns={getCalculatorColumns(handleDelete)}
                        data={calculators}
                        filterColumn="name"
                    />
                ) : (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Calculator className="h-16 w-16 text-gray-400 mb-4" />
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                No hay calculators
                            </h3>
                            <p className="text-gray-500 mb-4 text-center">
                                Crea tu primer calculator para comenzar a trabajar
                            </p>
                            <Button onClick={handleNewCalculator}>
                                <Plus className="mr-2 h-4 w-4" />
                                Crear Calculator
                            </Button>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Create Calculator Modal */}
            <Dialog
                open={isCreateModalOpen}
                onOpenChange={setIsCreateModalOpen}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Crear Nuevo Calculator</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nombre del Calculator</Label>
                            <Input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData("name", e.target.value)}
                                placeholder="Mi nuevo calculator"
                                autoFocus
                            />
                            {errors.name && (
                                <p className="text-sm text-red-500">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div className="flex justify-end gap-3 pt-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCreateModalOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing
                                    ? "Creando..."
                                    : "Crear Calculator"}
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
                title="¿Eliminar calculator?"
                description={`¿Estás seguro de eliminar el calculator "${deleteDialog.name}"? Esta acción no se puede deshacer.`}
                confirmText="Eliminar"
                cancelText="Cancelar"
                variant="destructive"
            />
        </AppLayout>
    );
}
