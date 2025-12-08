import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { router } from "@inertiajs/react";
import { AlertCircle, ArrowUpDown, Eye, RefreshCw, Trash2 } from "lucide-react";

export const getLeadColumns = (handleDelete, handleRetry) => [
    {
        id: "select",
        header: ({ table }) => (
            <Checkbox
                checked={table.getIsAllPageRowsSelected()}
                onCheckedChange={(value) =>
                    table.toggleAllPageRowsSelected(!!value)
                }
                aria-label="Seleccionar todo"
            />
        ),
        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Seleccionar fila"
            />
        ),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: "phone",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === "asc")
                    }
                >
                    Teléfono
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => (
            <div className="font-medium">{row.getValue("phone")}</div>
        ),
    },
    {
        accessorKey: "name",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === "asc")
                    }
                >
                    Nombre
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => <div>{row.getValue("name") || "-"}</div>,
    },
    {
        accessorKey: "city",
        header: "Ciudad",
        cell: ({ row }) => <div>{row.getValue("city") || "-"}</div>,
    },
    {
        accessorKey: "campaign",
        header: "Campaña",
        cell: ({ row }) => <div>{row.original.campaign?.name || "-"}</div>,
        enableSorting: false,
    },
    {
        accessorKey: "status",
        header: "Estado",
        cell: ({ row }) => {
            const status = row.getValue("status");
            const colors = {
                pending: "bg-yellow-100 text-yellow-800 hover:bg-yellow-100",
                in_progress: "bg-blue-100 text-blue-800 hover:bg-blue-100",
                contacted: "bg-purple-100 text-purple-800 hover:bg-purple-100",
                closed: "bg-green-100 text-green-800 hover:bg-green-100",
                invalid: "bg-red-100 text-red-800 hover:bg-red-100",
            };
            return (
                <Badge
                    className={colors[status] || "bg-gray-100 text-gray-800"}
                >
                    {row.original.status_label}
                </Badge>
            );
        },
    },
    {
        accessorKey: "source",
        header: "Fuente",
        cell: ({ row }) => (
            <div className="capitalize">{row.original.source_label || "-"}</div>
        ),
    },
    {
        accessorKey: "option_selected",
        header: "Opción",
        cell: ({ row }) => {
            const option = row.getValue("option_selected");
            return option ? (
                <Badge variant="outline">Opción {option}</Badge>
            ) : (
                <span className="text-muted-foreground">-</span>
            );
        },
    },
    {
        accessorKey: "automation_status",
        header: "Auto-Proceso",
        cell: ({ row }) => {
            const status = row.getValue("automation_status");
            const label = row.original.automation_status_label;
            const error = row.original.automation_error;

            if (!status)
                return <span className="text-muted-foreground">-</span>;

            const colors = {
                pending: "bg-yellow-100 text-yellow-800 hover:bg-yellow-100",
                processing: "bg-blue-100 text-blue-800 hover:bg-blue-100",
                completed: "bg-green-100 text-green-800 hover:bg-green-100",
                failed: "bg-red-100 text-red-800 hover:bg-red-100",
                skipped: "bg-gray-100 text-gray-800 hover:bg-gray-100",
            };

            return (
                <div className="flex items-center gap-2">
                    <Badge
                        className={
                            colors[status] || "bg-gray-100 text-gray-800"
                        }
                    >
                        {label}
                    </Badge>
                    {error && (
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <AlertCircle className="h-4 w-4 text-red-600 cursor-help" />
                                </TooltipTrigger>
                                <TooltipContent className="max-w-md">
                                    <p className="whitespace-normal break-words">
                                        {error}
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    )}
                </div>
            );
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const lead = row.original;
            return (
                <div className="flex justify-end gap-2">
                    {lead.can_retry_automation && (
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleRetry(lead)}
                            title="Reintentar procesamiento"
                        >
                            <RefreshCw className="h-4 w-4 text-blue-500" />
                        </Button>
                    )}
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                            router.visit(route("leads-manager.show", lead.id))
                        }
                    >
                        <Eye className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleDelete(lead)}
                    >
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            );
        },
    },
];
