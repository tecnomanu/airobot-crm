import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Checkbox } from "@/Components/ui/checkbox";
import { AlertCircle, RefreshCw, User, Users } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";

/**
 * AssigneesTab - Configure sales representatives for lead assignment.
 *
 * Allows selecting which users receive leads via round-robin when
 * leads become "Sales Ready".
 */
export default function AssigneesTab({
    campaign,
    availableUsers = [],
    onSave,
}) {
    const [selectedUserIds, setSelectedUserIds] = useState([]);
    const [isSaving, setIsSaving] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [assignees, setAssignees] = useState([]);
    const [cursor, setCursor] = useState(null);

    // Load current assignees from API
    useEffect(() => {
        if (campaign?.id) {
            loadAssignees();
        }
    }, [campaign?.id]);

    const loadAssignees = async () => {
        setIsLoading(true);
        try {
            const response = await fetch(`/panel-api/campaigns/${campaign.id}/assignees`);
            if (response.ok) {
                const data = await response.json();
                setAssignees(data.assignees || []);
                setCursor(data.cursor);
                setSelectedUserIds((data.assignees || []).map(a => a.user_id));
            }
        } catch (error) {
            console.error("Error loading assignees:", error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleToggleUser = (userId) => {
        setSelectedUserIds(prev => {
            if (prev.includes(userId)) {
                return prev.filter(id => id !== userId);
            }
            return [...prev, userId];
        });
    };

    const handleSave = async () => {
        setIsSaving(true);
        try {
            const response = await fetch(`/panel-api/campaigns/${campaign.id}/assignees/sync`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ user_ids: selectedUserIds }),
            });

            if (response.ok) {
                toast.success("Vendedores actualizados");
                loadAssignees();
            } else {
                const error = await response.json();
                toast.error(error.message || "Error al guardar");
            }
        } catch (error) {
            toast.error("Error de conexión");
        } finally {
            setIsSaving(false);
        }
    };

    // Get the next assignee name based on cursor
    const getNextAssigneeName = () => {
        if (!cursor || assignees.length === 0) return null;
        const nextAssignee = assignees[cursor.current_index];
        return nextAssignee?.user?.name || null;
    };

    const hasChanges = () => {
        const currentIds = assignees.map(a => a.user_id).sort();
        const newIds = [...selectedUserIds].sort();
        return JSON.stringify(currentIds) !== JSON.stringify(newIds);
    };

    if (isLoading) {
        return (
            <Card>
                <CardContent className="flex items-center justify-center py-8">
                    <RefreshCw className="h-6 w-6 animate-spin text-muted-foreground" />
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header Card */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Users className="h-5 w-5" />
                        Vendedores Asignados
                    </CardTitle>
                    <CardDescription>
                        Selecciona los vendedores que recibirán leads de esta campaña.
                        La asignación se realiza automáticamente usando round-robin cuando un lead
                        pasa a estado "Sales Ready" (interesado y finalizado).
                    </CardDescription>
                </CardHeader>
            </Card>

            {/* Current Status */}
            {assignees.length > 0 && (
                <Card className="border-green-200 bg-green-50/30">
                    <CardContent className="pt-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <Users className="h-5 w-5 text-green-600" />
                                </div>
                                <div>
                                    <p className="font-medium text-green-900">
                                        {assignees.length} vendedor{assignees.length !== 1 ? "es" : ""} configurado{assignees.length !== 1 ? "s" : ""}
                                    </p>
                                    {getNextAssigneeName() && (
                                        <p className="text-sm text-green-700">
                                            Próximo en recibir: <span className="font-medium">{getNextAssigneeName()}</span>
                                        </p>
                                    )}
                                </div>
                            </div>
                            {cursor?.last_assigned_at && (
                                <Badge variant="outline" className="text-green-700 border-green-300">
                                    Última asignación: {new Date(cursor.last_assigned_at).toLocaleDateString()}
                                </Badge>
                            )}
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Warning if no assignees */}
            {assignees.length === 0 && (
                <Card className="border-amber-200 bg-amber-50/30">
                    <CardContent className="pt-4">
                        <div className="flex items-center gap-3">
                            <AlertCircle className="h-5 w-5 text-amber-600" />
                            <div>
                                <p className="font-medium text-amber-900">
                                    Sin vendedores configurados
                                </p>
                                <p className="text-sm text-amber-700">
                                    Los leads que lleguen a "Sales Ready" quedarán con error de configuración
                                    hasta que asignes vendedores.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* User Selection */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Seleccionar Vendedores</CardTitle>
                    <CardDescription>
                        Marca los usuarios que participarán en la rotación de asignación.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-2">
                        {availableUsers.length === 0 ? (
                            <p className="text-sm text-muted-foreground text-center py-4">
                                No hay usuarios disponibles para asignar.
                            </p>
                        ) : (
                            availableUsers.map((user) => (
                                <label
                                    key={user.id}
                                    className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors ${
                                        selectedUserIds.includes(user.id)
                                            ? "bg-indigo-50 border-indigo-200"
                                            : "bg-white border-gray-200 hover:border-gray-300"
                                    }`}
                                >
                                    <Checkbox
                                        checked={selectedUserIds.includes(user.id)}
                                        onCheckedChange={() => handleToggleUser(user.id)}
                                    />
                                    <div className="flex items-center gap-3 flex-1">
                                        <div className="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                                            <User className="h-4 w-4 text-gray-600" />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <p className="font-medium text-sm truncate">{user.name}</p>
                                            <p className="text-xs text-muted-foreground truncate">{user.email}</p>
                                        </div>
                                    </div>
                                    {selectedUserIds.includes(user.id) && (
                                        <Badge variant="secondary" className="text-xs">
                                            #{selectedUserIds.indexOf(user.id) + 1}
                                        </Badge>
                                    )}
                                </label>
                            ))
                        )}
                    </div>

                    {hasChanges() && (
                        <div className="mt-4 flex justify-end">
                            <Button onClick={handleSave} disabled={isSaving}>
                                {isSaving ? (
                                    <>
                                        <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
                                        Guardando...
                                    </>
                                ) : (
                                    "Guardar Cambios"
                                )}
                            </Button>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Info about round-robin */}
            <Card className="bg-slate-50/50">
                <CardContent className="pt-4">
                    <div className="flex gap-3">
                        <div className="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
                            <RefreshCw className="h-4 w-4 text-slate-600" />
                        </div>
                        <div className="text-sm text-slate-600">
                            <p className="font-medium text-slate-700 mb-1">Asignación Round-Robin</p>
                            <p>
                                Cada vez que un lead pasa a "Sales Ready", se asigna automáticamente
                                al siguiente vendedor en la lista. El orden se determina por el orden
                                de selección y se mantiene determinístico.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

