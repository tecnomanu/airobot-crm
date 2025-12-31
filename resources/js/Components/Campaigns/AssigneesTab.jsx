import { Badge } from "@/Components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Checkbox } from "@/Components/ui/checkbox";
import { AlertCircle, Info, RefreshCw, UserCheck, Users } from "lucide-react";
import { useEffect, useState } from "react";

/**
 * AssigneesTab - Configure sales representatives for lead assignment.
 *
 * Allows selecting which users receive leads via round-robin when
 * leads become "Sales Ready".
 * 
 * NOTE: Changes are saved when the campaign form is saved (no separate save button).
 */
export default function AssigneesTab({
    data,
    setData,
    campaign,
    availableUsers = [],
    errors,
}) {
    const [isLoading, setIsLoading] = useState(true);
    const [cursor, setCursor] = useState(null);
    const [currentAssignees, setCurrentAssignees] = useState([]);

    // Initialize selected users from data prop or load from API
    useEffect(() => {
        if (campaign?.id) {
            loadAssignees();
        } else {
            setIsLoading(false);
        }
    }, [campaign?.id]);

    const loadAssignees = async () => {
        setIsLoading(true);
        try {
            const response = await fetch(`/panel-api/campaigns/${campaign.id}/assignees`);
            if (response.ok) {
                const result = await response.json();
                setCurrentAssignees(result.assignees || []);
                setCursor(result.cursor);

                // Initialize form data with current assignees
                const userIds = (result.assignees || []).map(a => a.user_id);
                if (!data.assignee_user_ids) {
                    setData('assignee_user_ids', userIds);
                }
            }
        } catch (error) {
            console.error("Error loading assignees:", error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleToggleUser = (userId) => {
        const currentIds = data.assignee_user_ids || [];
        let newIds;

        if (currentIds.includes(userId)) {
            newIds = currentIds.filter(id => id !== userId);
        } else {
            newIds = [...currentIds, userId];
        }

        setData('assignee_user_ids', newIds);
    };

    const selectedUserIds = data.assignee_user_ids || [];

    // Get the next assignee name based on cursor
    const getNextAssigneeName = () => {
        if (!cursor || currentAssignees.length === 0) return null;
        const nextAssignee = currentAssignees[cursor.current_index];
        return nextAssignee?.user?.name || null;
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

            {/* User Selection */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="text-base">Seleccionar Vendedores</CardTitle>
                    <CardDescription>
                        Marca los usuarios que participarán en la rotación de asignación.
                        Solo se muestran usuarios del cliente asociado marcados como vendedores.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-2">
                        {availableUsers.length === 0 ? (
                            <div className="text-center py-6 px-4 bg-slate-50 rounded-lg border border-slate-200">
                                <UserCheck className="h-8 w-8 text-slate-400 mx-auto mb-2" />
                                <p className="text-sm font-medium text-slate-700">
                                    No hay vendedores disponibles
                                </p>
                                <p className="text-xs text-slate-500 mt-1">
                                    Ve a Usuarios y marca usuarios como vendedores para este cliente
                                </p>
                            </div>
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
                                        <div className="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                            <UserCheck className="h-4 w-4 text-green-600" />
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
                </CardContent>
            </Card>

            {/* Current Status - Show only if there are current assignees */}
            {currentAssignees.length > 0 && (
                <Card className="border-green-200 bg-green-50/30">
                    <CardContent className="pt-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <Users className="h-5 w-5 text-green-600" />
                                </div>
                                <div>
                                    <p className="font-medium text-green-900">
                                        {currentAssignees.length} vendedor{currentAssignees.length !== 1 ? "es" : ""} activo{currentAssignees.length !== 1 ? "s" : ""}
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

            {/* Warning if no assignees selected */}
            {selectedUserIds.length === 0 && (
                <Card className="border-amber-200 bg-amber-50/30">
                    <CardContent className="pt-4">
                        <div className="flex items-start gap-3">
                            <AlertCircle className="h-5 w-5 text-amber-600 mt-0.5 shrink-0" />
                            <div>
                                <p className="font-medium text-amber-900">
                                    Sin vendedores asignados
                                </p>
                                <p className="text-sm text-amber-700 mt-1">
                                    Si no asignas vendedores, los leads que lleguen a "Sales Ready" se asignarán 
                                    automáticamente al creador del cliente. Si no existe creador, quedarán 
                                    con error de configuración visible en el tab "Errores".
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Info about round-robin */}
            <Card className="bg-slate-50/50 border-slate-200">
                <CardContent className="pt-4">
                    <div className="flex gap-3">
                        <div className="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
                            <Info className="h-4 w-4 text-slate-600" />
                        </div>
                        <div className="text-sm text-slate-600">
                            <p className="font-medium text-slate-700 mb-1">Asignación Round-Robin</p>
                            <p>
                                Cada vez que un lead pasa a "Sales Ready", se asigna automáticamente
                                al siguiente vendedor en la lista. El orden se determina por el orden
                                de selección y se mantiene determinístico entre asignaciones.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
