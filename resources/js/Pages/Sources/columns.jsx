import { ArrowUpDown, Eye, Pencil, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Badge } from "@/components/ui/badge";
import { router } from "@inertiajs/react";

export const getSourceColumns = (handleEdit, handleDelete) => [
    {
        id: "select",
        header: ({ table }) => (
            <Checkbox
                checked={table.getIsAllPageRowsSelected()}
                onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
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
        accessorKey: "name",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                >
                    Nombre
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => <div className="font-medium">{row.getValue("name")}</div>,
    },
    {
        accessorKey: "type",
        header: "Tipo",
        cell: ({ row }) => {
            const type = row.getValue("type");
            const typeColors = {
                whatsapp: "bg-green-100 text-green-800 hover:bg-green-100",
                webhook: "bg-blue-100 text-blue-800 hover:bg-blue-100",
                meta_whatsapp: "bg-green-100 text-green-800 hover:bg-green-100",
                facebook_lead_ads: "bg-indigo-100 text-indigo-800 hover:bg-indigo-100",
                google_ads: "bg-orange-100 text-orange-800 hover:bg-orange-100",
            };
            return (
                <div className="flex flex-col gap-1">
                    <Badge className={typeColors[type] || "bg-gray-100 text-gray-800"}>
                        {row.original.type_base}
                    </Badge>
                    <span className="text-xs text-muted-foreground">{row.original.provider}</span>
                </div>
            );
        },
    },
    {
        accessorKey: "detail",
        header: "Detalle",
        cell: ({ row }) => {
            const source = row.original;
            const config = source.config || {};
            
            // Mostrar detalle según el tipo
            let detail = "-";
            if (source.type === "whatsapp") {
                detail = config.phone_number || config.instance_name || "-";
            } else if (source.type === "webhook") {
                detail = config.url || "-";
            } else if (source.type === "meta_whatsapp") {
                detail = config.phone_number_id || "-";
            }
            
            return <div className="max-w-xs truncate">{detail}</div>;
        },
        enableSorting: false,
    },
    {
        accessorKey: "status",
        header: "Estado",
        cell: ({ row }) => {
            const status = row.getValue("status");
            const statusColors = {
                active: "bg-green-100 text-green-800 hover:bg-green-100",
                inactive: "bg-gray-100 text-gray-800 hover:bg-gray-100",
                error: "bg-red-100 text-red-800 hover:bg-red-100",
                pending_setup: "bg-yellow-100 text-yellow-800 hover:bg-yellow-100",
            };
            return (
                <Badge className={statusColors[status] || "bg-gray-100 text-gray-800"}>
                    {row.original.status_label}
                </Badge>
            );
        },
    },
    {
        accessorKey: "client",
        header: "Cliente",
        cell: ({ row }) => <div>{row.original.client?.name || "-"}</div>,
        enableSorting: false,
    },
    {
        accessorKey: "campaigns_count",
        header: "Campañas",
        cell: ({ row }) => {
            const count = row.original.campaigns_count || 0;
            return (
                <Badge variant="outline">
                    {count}
                </Badge>
            );
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const source = row.original;
            return (
                <div className="flex justify-end gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleEdit(source)}
                        title="Editar"
                    >
                        <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleDelete(source)}
                        title="Eliminar"
                    >
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            );
        },
    },
];

