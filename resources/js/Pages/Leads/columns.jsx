import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import {
    AlertTriangle,
    Bot,
    Calendar,
    CheckCircle,
    Clock,
    Edit,
    Eye,
    Loader2,
    MessageSquare,
    MoreHorizontal,
    Pause,
    Phone,
    RefreshCw,
    Trash2,
    User,
    XCircle,
} from "lucide-react";

/**
 * Stage badge component - derives visible stage from backend computed fields
 */
const StageBadge = ({ stage, label, color }) => {
    const colorMap = {
        blue: "bg-blue-50 text-blue-700 border-blue-200",
        indigo: "bg-indigo-50 text-indigo-700 border-indigo-200",
        purple: "bg-purple-50 text-purple-700 border-purple-200",
        yellow: "bg-yellow-50 text-yellow-700 border-yellow-200",
        green: "bg-green-50 text-green-700 border-green-200",
        red: "bg-red-50 text-red-700 border-red-200",
        gray: "bg-gray-50 text-gray-700 border-gray-200",
    };

    return (
        <Badge
            variant="outline"
            className={`${
                colorMap[color] || colorMap.gray
            } text-[10px] font-medium px-2 py-0.5`}
        >
            {label || stage?.toUpperCase()}
        </Badge>
    );
};

/**
 * Automation status badge - shows current automation state
 */
const AutomationBadge = ({ status, label, color, error }) => {
    const colorMap = {
        yellow: "bg-yellow-50 text-yellow-700 border-yellow-200",
        blue: "bg-blue-50 text-blue-700 border-blue-200",
        green: "bg-green-50 text-green-700 border-green-200",
        red: "bg-red-50 text-red-700 border-red-200",
        gray: "bg-gray-50 text-gray-700 border-gray-200",
    };

    const iconMap = {
        pending: Clock,
        processing: Loader2,
        completed: CheckCircle,
        failed: XCircle,
        skipped: Pause,
    };

    const Icon = iconMap[status] || Clock;
    const isAnimated = status === "processing";

    if (error) {
        return (
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Badge
                            variant="outline"
                            className="bg-red-50 text-red-700 border-red-200 text-[10px] font-medium px-2 py-0.5 cursor-help"
                        >
                            <AlertTriangle className="h-3 w-3 mr-1" />
                            Error
                        </Badge>
                    </TooltipTrigger>
                    <TooltipContent className="max-w-xs">
                        <p className="text-xs">{error}</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        );
    }

    return (
        <Badge
            variant="outline"
            className={`${
                colorMap[color] || colorMap.gray
            } text-[10px] font-medium px-2 py-0.5`}
        >
            <Icon
                className={`h-3 w-3 mr-1 ${isAnimated ? "animate-spin" : ""}`}
            />
            {label || status}
        </Badge>
    );
};

/**
 * Source badge component
 */
const SourceBadge = ({ source, label }) => {
    const colors = {
        ivr: "bg-emerald-50 text-emerald-700 border-emerald-200",
        webhook: "bg-green-50 text-green-700 border-green-200",
        webhook_inicial: "bg-green-50 text-green-700 border-green-200",
        csv: "bg-blue-50 text-blue-700 border-blue-200",
        manual: "bg-gray-50 text-gray-700 border-gray-200",
        whatsapp: "bg-orange-50 text-orange-700 border-orange-200",
        agente_ia: "bg-purple-50 text-purple-700 border-purple-200",
    };

    const sourceKey = source?.toLowerCase() || "";
    const colorClass =
        colors[sourceKey] || "bg-gray-50 text-gray-700 border-gray-200";

    return (
        <Badge
            variant="outline"
            className={`${colorClass} text-[10px] font-medium px-2 py-0.5`}
        >
            {(label || source || "UNKNOWN").toUpperCase()}
        </Badge>
    );
};

/**
 * Format relative date for display
 */
const formatRelativeDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return "Ahora";
    if (diffMins < 60) return `${diffMins}m`;
    if (diffHours < 24) return `${diffHours}h`;
    if (diffDays < 7) return `${diffDays}d`;

    return date.toLocaleDateString("es-ES", {
        day: "2-digit",
        month: "2-digit",
    });
};

/**
 * Format next action date
 */
const formatNextAction = (dateString, label) => {
    if (!dateString) return null;

    const date = new Date(dateString);
    const now = new Date();
    const isPast = date < now;

    return {
        label: label || date.toLocaleDateString("es-ES", {
            day: "2-digit",
            month: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
        }),
        isPast,
    };
};

/**
 * Column definitions for leads table
 * Shows: NAME, STAGE, AUTOMATION, NEXT ACTION, LAST ACTIVITY, SOURCE, ACTIONS
 */
export const getLeadColumns = (activeTab, handlers = {}) => {
    const { onDelete, onCall, onWhatsApp, onView, onEdit, onRetryAutomation } =
        handlers;

    // Base columns present in all views
    const baseColumns = [
        // NAME Column - Name + Phone
        {
            accessorKey: "name",
            header: "LEAD",
            cell: ({ row }) => {
                const lead = row.original;
                return (
                    <div className="flex flex-col min-w-0">
                        <span className="text-sm font-medium text-gray-900 truncate">
                            {lead.name || "Sin nombre"}
                        </span>
                        <span className="text-xs text-gray-500 font-mono">
                            {lead.phone}
                        </span>
                    </div>
                );
            },
        },

        // STAGE Column - Computed stage from backend
        {
            accessorKey: "stage",
            header: "ETAPA",
            cell: ({ row }) => {
                const lead = row.original;
                return (
                    <StageBadge
                        stage={lead.stage}
                        label={lead.stage_label}
                        color={lead.stage_color}
                    />
                );
            },
        },

        // AUTOMATION Column - Shows automation status
        {
            accessorKey: "automation_status",
            header: "AUTOMATIZACIÓN",
            cell: ({ row }) => {
                const lead = row.original;
                
                if (!lead.automation_status) {
                    return <span className="text-xs text-gray-400">—</span>;
                }

                return (
                    <AutomationBadge
                        status={lead.automation_status}
                        label={lead.automation_status_label}
                        color={lead.automation_status_color}
                        error={lead.automation_error}
                    />
                );
            },
        },

        // NEXT ACTION Column
        {
            accessorKey: "next_action_at",
            header: "PRÓXIMA ACCIÓN",
            cell: ({ row }) => {
                const lead = row.original;
                const nextAction = formatNextAction(
                    lead.next_action_at,
                    lead.next_action_label
                );

                if (!nextAction) {
                    return <span className="text-xs text-gray-400">—</span>;
                }

                return (
                    <div
                        className={`flex items-center gap-1 text-xs ${
                            nextAction.isPast
                                ? "text-red-600 font-medium"
                                : "text-gray-600"
                        }`}
                    >
                        <Calendar className="h-3 w-3" />
                        <span>{nextAction.label}</span>
                    </div>
                );
            },
        },

        // LAST ACTIVITY Column
        {
            accessorKey: "updated_at",
            header: "ÚLTIMA ACTIVIDAD",
            cell: ({ row }) => (
                <span className="text-xs text-gray-500">
                    {formatRelativeDate(row.original.updated_at)}
                </span>
            ),
        },

        // SOURCE Column (secondary)
        {
            accessorKey: "source",
            header: "FUENTE",
            cell: ({ row }) => {
                const lead = row.original;
                return <SourceBadge source={lead.source} label={lead.source_label} />;
            },
        },
    ];

    // Conditional columns based on tab
    const conditionalColumns = [];

    // IVR Entry column for active/inbox tabs with IVR leads
    if (["inbox", "active"].includes(activeTab)) {
        conditionalColumns.push({
            accessorKey: "option_selected",
            header: "ENTRADA IVR",
            cell: ({ row }) => {
                const option = row.original.option_selected;

                if (!option) {
                    return <span className="text-xs text-gray-300">—</span>;
                }

                return (
                    <Badge
                        variant="outline"
                        className="bg-indigo-50 text-indigo-700 border-indigo-200 text-[10px] font-medium px-2 py-0.5"
                    >
                        Opt: {option}
                    </Badge>
                );
            },
        });
    }

    // Assignee column for sales_ready tab
    if (activeTab === "sales_ready") {
        conditionalColumns.push({
            accessorKey: "assignee",
            header: "ASIGNADO A",
            cell: ({ row }) => {
                const lead = row.original;
                const assignee = lead.assignee;
                const hasError = lead.assignment_error;

                if (hasError) {
                    return (
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Badge
                                        variant="outline"
                                        className="bg-red-50 text-red-700 border-red-200 text-[10px] font-medium px-2 py-0.5 cursor-help"
                                    >
                                        <AlertTriangle className="h-3 w-3 mr-1" />
                                        Sin asignar
                                    </Badge>
                                </TooltipTrigger>
                                <TooltipContent className="max-w-xs">
                                    <p className="text-xs">{hasError}</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    );
                }

                if (!assignee) {
                    return <span className="text-xs text-gray-400">—</span>;
                }

                return (
                    <div className="flex items-center gap-2">
                        <div className="h-6 w-6 rounded-full bg-indigo-100 flex items-center justify-center">
                            <User className="h-3 w-3 text-indigo-600" />
                        </div>
                        <span className="text-xs font-medium text-gray-700 truncate max-w-[100px]">
                            {assignee.name}
                        </span>
                    </div>
                );
            },
        });
    }

    // Error details for errors tab
    if (activeTab === "errors") {
        conditionalColumns.push({
            accessorKey: "automation_error",
            header: "DETALLE ERROR",
            cell: ({ row }) => {
                const error = row.original.automation_error;

                if (!error) {
                    return <span className="text-xs text-gray-400">—</span>;
                }

                return (
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <span className="text-xs text-red-600 truncate max-w-[150px] block cursor-help">
                                    {error.substring(0, 30)}...
                                </span>
                            </TooltipTrigger>
                            <TooltipContent className="max-w-xs">
                                <p className="text-xs">{error}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                );
            },
        });
    }

    // ACTIONS Column - Always last
    const actionsColumn = {
        id: "actions",
        header: "",
        enableHiding: false,
        cell: ({ row }) => {
            const lead = row.original;
            const canRetry = lead.can_retry_automation;

            return (
                <div
                    className="flex justify-end"
                    onClick={(e) => e.stopPropagation()}
                >
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8 hover:bg-gray-100"
                            >
                                <MoreHorizontal className="h-4 w-4" />
                                <span className="sr-only">Abrir menú</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                            {/* View */}
                            <DropdownMenuItem
                                onClick={() => onView?.(lead)}
                                className="cursor-pointer"
                            >
                                <Eye className="mr-2 h-4 w-4" />
                                Ver detalles
                            </DropdownMenuItem>

                            {/* Edit */}
                            <DropdownMenuItem
                                onClick={() => onEdit?.(lead)}
                                className="cursor-pointer"
                            >
                                <Edit className="mr-2 h-4 w-4" />
                                Editar
                            </DropdownMenuItem>

                            <DropdownMenuSeparator />

                            {/* Communication Actions */}
                            <DropdownMenuItem
                                onClick={() => onCall?.(lead)}
                                className="cursor-pointer"
                            >
                                <Phone className="mr-2 h-4 w-4 text-green-600" />
                                Llamar
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                onClick={() => onWhatsApp?.(lead)}
                                className="cursor-pointer"
                            >
                                <MessageSquare className="mr-2 h-4 w-4 text-blue-600" />
                                Abrir WhatsApp
                            </DropdownMenuItem>

                            <DropdownMenuSeparator />

                            {/* Retry automation - only if applicable */}
                            {canRetry && (
                                <>
                                    <DropdownMenuItem
                                        onClick={() =>
                                            onRetryAutomation?.(lead)
                                        }
                                        className="cursor-pointer"
                                    >
                                        <RefreshCw className="mr-2 h-4 w-4 text-purple-600" />
                                        Reintentar automatización
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                </>
                            )}

                            {/* Destructive */}
                            <DropdownMenuItem
                                onClick={() => onDelete?.(lead)}
                                className="cursor-pointer text-red-600 focus:text-red-600 focus:bg-red-50"
                            >
                                <Trash2 className="mr-2 h-4 w-4" />
                                Eliminar
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            );
        },
    };

    // Insert conditional columns before SOURCE and ACTIONS
    const sourceIndex = baseColumns.findIndex(
        (col) => col.accessorKey === "source"
    );
    const columnsBeforeSource = baseColumns.slice(0, sourceIndex);
    const sourceColumn = baseColumns[sourceIndex];

    return [
        ...columnsBeforeSource,
        ...conditionalColumns,
        sourceColumn,
        actionsColumn,
    ];
};
