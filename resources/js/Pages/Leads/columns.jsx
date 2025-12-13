import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
    Edit,
    Eye,
    MessageSquare,
    MoreHorizontal,
    Phone,
    RefreshCw,
    Trash2,
} from "lucide-react";

/**
 * Status badge component for leads
 */
const StatusBadge = ({ status, label }) => {
    const colors = {
        pending: "bg-blue-50 text-blue-700 border-blue-200",
        new: "bg-blue-50 text-blue-700 border-blue-200",
        in_progress: "bg-yellow-50 text-yellow-700 border-yellow-200",
        qualifying: "bg-yellow-50 text-yellow-700 border-yellow-200",
        contacted: "bg-purple-50 text-purple-700 border-purple-200",
        sales_ready: "bg-green-50 text-green-700 border-green-200",
        closed: "bg-gray-50 text-gray-700 border-gray-200",
        invalid: "bg-red-50 text-red-700 border-red-200",
    };

    return (
        <Badge
            variant="outline"
            className={`${
                colors[status] || "bg-gray-50 text-gray-700"
            } text-[10px] font-medium px-2 py-0.5`}
        >
            {label || status?.toUpperCase()}
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
 * Format date for display
 */
const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return date.toLocaleDateString("es-ES", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
};

/**
 * Column definitions for leads table
 * Shows: NAME (with phone), SOURCE, STATUS, CREATED, ACTIONS
 */
export const getLeadColumns = (activeTab, handlers = {}) => {
    const { onDelete, onCall, onWhatsApp, onView, onEdit, onRetryAutomation } =
        handlers;

    return [
        // NAME Column - Name + Phone
        {
            accessorKey: "name",
            header: "NAME",
            cell: ({ row }) => {
                const lead = row.original;
                return (
                    <div className="flex flex-col min-w-0">
                        <span className="text-sm font-medium text-gray-900 truncate">
                            {lead.name || "Unknown User"}
                        </span>
                        <span className="text-xs text-gray-500">
                            {lead.phone}
                        </span>
                    </div>
                );
            },
        },

        // SOURCE Column
        {
            accessorKey: "source",
            header: "SOURCE",
            cell: ({ row }) => {
                const lead = row.original;
                const sourceText =
                    lead.source_label || lead.source || "Unknown";

                return <SourceBadge source={lead.source} label={sourceText} />;
            },
        },

        // OPTION Column - IVR option selected (only shown if exists)
        {
            accessorKey: "option_selected",
            header: "OPTION",
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
        },

        // STATUS Column
        {
            accessorKey: "status",
            header: "STATUS",
            cell: ({ row }) => {
                const lead = row.original;
                let displayLabel = lead.status_label;

                // Custom labels based on tab
                if (activeTab === "inbox") {
                    displayLabel = "NEW";
                } else if (activeTab === "active") {
                    displayLabel = "QUALIFYING";
                } else if (activeTab === "sales_ready") {
                    displayLabel = "SALES READY";
                }

                return (
                    <StatusBadge status={lead.status} label={displayLabel} />
                );
            },
        },

        // CREATED Column
        {
            accessorKey: "created_at",
            header: "CREATED",
            cell: ({ row }) => (
                <span className="text-sm text-gray-600">
                    {formatDate(row.original.created_at)}
                </span>
            ),
        },

        // ACTIONS Column - Dropdown Menu
        {
            id: "actions",
            header: "",
            enableHiding: false,
            cell: ({ row }) => {
                const lead = row.original;
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

                                {/* Campaign Actions */}
                                <DropdownMenuItem
                                    onClick={() => onRetryAutomation?.(lead)}
                                    className="cursor-pointer"
                                >
                                    <RefreshCw className="mr-2 h-4 w-4 text-purple-600" />
                                    Re-ejecutar campaña
                                </DropdownMenuItem>

                                <DropdownMenuSeparator />

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
        },
    ];
};
