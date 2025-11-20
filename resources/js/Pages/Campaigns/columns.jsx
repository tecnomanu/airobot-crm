import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Switch } from "@/components/ui/switch";
import { router } from "@inertiajs/react";
import { ArrowUpDown, Eye, Trash2 } from "lucide-react";

export const getCampaignColumns = (handleDelete, handleToggleStatus) => [
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
        cell: ({ row }) => (
            <div className="font-medium">{row.getValue("name")}</div>
        ),
    },
    {
        accessorKey: "client",
        header: "Cliente",
        cell: ({ row }) => <div>{row.original.client?.name || "-"}</div>,
        enableSorting: false,
    },
    {
        accessorKey: "slug",
        header: "Slug",
        cell: ({ row }) => (
            <div className="font-mono text-xs">
                {row.getValue("slug") || (
                    <span className="text-muted-foreground">-</span>
                )}
            </div>
        ),
    },
    {
        accessorKey: "webhook_enabled",
        header: "Webhook",
        cell: ({ row }) => {
            const enabled = row.getValue("webhook_enabled");
            return enabled ? (
                <Badge className="bg-green-100 text-green-800 hover:bg-green-100">
                    Activo
                </Badge>
            ) : (
                <Badge variant="outline">Inactivo</Badge>
            );
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const campaign = row.original;
            const isActive = campaign.status === "active";

            return (
                <div className="flex justify-end items-center gap-3">
                    <Switch
                        checked={isActive}
                        onCheckedChange={() => handleToggleStatus(campaign)}
                        className="data-[state=checked]:bg-green-600"
                    />
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                            router.visit(route("campaigns.show", campaign.id))
                        }
                    >
                        <Eye className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleDelete(campaign)}
                    >
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            );
        },
    },
];
