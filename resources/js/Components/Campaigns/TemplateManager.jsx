import { useState } from "react";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import { Plus, Edit, Trash2, Info } from "lucide-react";
import { router, useForm } from "@inertiajs/react";
import { toast } from "sonner";

export default function TemplateManager({ campaign, templates = [], selectedWhatsappSource }) {
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingTemplate, setEditingTemplate] = useState(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        code: "",
        name: "",
        body: "",
        attachments: [],
        is_default: false,
    });

    const handleCreateNew = () => {
        reset();
        setEditingTemplate(null);
        setIsDialogOpen(true);
    };

    const handleEdit = (template) => {
        setEditingTemplate(template);
        setData({
            code: template.code,
            name: template.name,
            body: template.body,
            attachments: template.attachments || [],
            is_default: template.is_default,
        });
        setIsDialogOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (editingTemplate) {
            put(
                route("api.campaigns.templates.update", {
                    campaignId: campaign.id,
                    templateId: editingTemplate.id,
                }),
                {
                    onSuccess: () => {
                        toast.success("Template actualizado");
                        setIsDialogOpen(false);
                        router.reload({ only: ["templates"] });
                    },
                    onError: () => toast.error("Error al actualizar template"),
                }
            );
        } else {
            post(route("api.campaigns.templates.store", campaign.id), {
                onSuccess: () => {
                    toast.success("Template creado");
                    setIsDialogOpen(false);
                    reset();
                    router.reload({ only: ["templates"] });
                },
                onError: () => toast.error("Error al crear template"),
            });
        }
    };

    const handleDelete = (templateId) => {
        if (confirm("¿Eliminar este template?")) {
            router.delete(
                route("api.campaigns.templates.destroy", {
                    campaignId: campaign.id,
                    templateId,
                }),
                {
                    onSuccess: () => toast.success("Template eliminado"),
                    onError: () => toast.error("Error al eliminar template"),
                }
            );
        }
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle>Plantillas de WhatsApp</CardTitle>
                        <CardDescription>
                            Gestiona las plantillas de mensajes para esta campaña
                        </CardDescription>
                    </div>
                    <Button onClick={handleCreateNew} size="sm">
                        <Plus className="mr-2 h-4 w-4" />
                        Nueva Plantilla
                    </Button>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                {selectedWhatsappSource && (
                    <div className="flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                        <Info className="h-4 w-4 mt-0.5 shrink-0" />
                        <p>
                            Estas plantillas se enviarán usando la fuente de WhatsApp:{" "}
                            <span className="font-semibold">{selectedWhatsappSource.name}</span>
                            {selectedWhatsappSource.config?.phone_number && (
                                <span className="text-blue-700"> ({selectedWhatsappSource.config.phone_number})</span>
                            )}
                        </p>
                    </div>
                )}
                <div>
                    {templates.length === 0 ? (
                        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-8 text-center">
                            <p className="text-sm text-muted-foreground">
                                No hay plantillas creadas aún
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {templates.map((template) => (
                            <div
                                key={template.id}
                                className="flex items-start justify-between rounded-lg border p-4"
                            >
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <h4 className="font-medium">{template.name}</h4>
                                        <span className="rounded bg-muted px-2 py-0.5 text-xs">
                                            {template.code}
                                        </span>
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground line-clamp-2">
                                        {template.body}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {template.body_length} caracteres
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleEdit(template)}
                                    >
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleDelete(template.id)}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            ))}
                        </div>
                    )}
                </div>
            </CardContent>

            {/* Dialog for Create/Edit */}
            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingTemplate ? "Editar" : "Nueva"} Plantilla
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData("name", e.target.value)}
                                    placeholder="Ej: Bienvenida Opción 1"
                                />
                                {errors.name && (
                                    <p className="text-sm text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="code">Código Interno *</Label>
                                <Input
                                    id="code"
                                    value={data.code}
                                    onChange={(e) => setData("code", e.target.value)}
                                    placeholder="Ej: option_1_initial"
                                />
                                {errors.code && (
                                    <p className="text-sm text-destructive">
                                        {errors.code}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="body">Mensaje *</Label>
                            <Textarea
                                id="body"
                                value={data.body}
                                onChange={(e) => setData("body", e.target.value)}
                                placeholder="Escribe el mensaje aquí..."
                                rows={6}
                            />
                            <div className="flex justify-between text-xs text-muted-foreground">
                                <span>
                                    {errors.body ? (
                                        <span className="text-destructive">
                                            {errors.body}
                                        </span>
                                    ) : (
                                        "El mensaje que recibirá el usuario"
                                    )}
                                </span>
                                <span>{data.body.length} caracteres</span>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsDialogOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {editingTemplate ? "Actualizar" : "Crear"} Plantilla
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </Card>
    );
}

