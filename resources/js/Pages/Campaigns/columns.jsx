import StatusSwitch from "@/Components/Common/StatusSwitch";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { router } from "@inertiajs/react";
import { Eye, Trash2, Zap, GitBranch } from "lucide-react";

export const getCampaignColumns = (handleDelete, handleToggleStatus) => [
    {
        accessorKey: "name",
        header: "NOMBRE",
        cell: ({ row }) => {
            const campaign = row.original;
            return (
                <div className="flex flex-col min-w-0">
                    <span className="text-sm font-medium text-gray-900 truncate">
                        {campaign.name}
                    </span>
                    {campaign.description && (
                        <span className="text-xs text-gray-500 truncate max-w-[200px]">
                            {campaign.description}
                        </span>
                    )}
                </div>
            );
        },
    },
    {
        accessorKey: "client",
        header: "CLIENTE",
        cell: ({ row }) => (
            <span className="text-sm text-gray-600">
                {row.original.client?.name || "-"}
            </span>
        ),
        enableSorting: false,
    },
    {
        accessorKey: "strategy_type",
        header: "TIPO",
        cell: ({ row }) => {
            const strategy = row.original.strategy_type;
            const isDirect = strategy === "direct";
            
            return (
                <Badge
                    variant="outline"
                    className={`text-[10px] font-medium px-2 py-0.5 flex items-center gap-1 w-fit ${
                        isDirect
                            ? "bg-green-50 text-green-700 border-green-200"
                            : "bg-blue-50 text-blue-700 border-blue-200"
                    }`}
                >
                    {isDirect ? (
                        <Zap className="h-3 w-3" />
                    ) : (
                        <GitBranch className="h-3 w-3" />
                    )}
                    {isDirect ? "DIRECTA" : "MÚLTIPLE"}
                </Badge>
            );
        },
    },
    {
        accessorKey: "leads_count",
        header: "LEADS",
        cell: ({ row }) => (
            <span className="text-sm text-gray-600">
                {row.original.leads_count ?? 0}
            </span>
        ),
    },
    {
        id: "status",
        header: "ESTADO",
        cell: ({ row }) => {
            const campaign = row.original;
            const isActive = campaign.status === "active";

            return (
                <StatusSwitch
                    checked={isActive}
                    onChange={() => handleToggleStatus(campaign)}
                    activeText="Activa"
                    inactiveText="Pausada"
                />
            );
        },
    },
    {
        id: "actions",
        header: "ACCIONES",
        enableHiding: false,
        cell: ({ row }) => {
            const campaign = row.original;

            return (
                <div className="flex justify-end items-center gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-7 w-7"
                        onClick={() => router.visit(route("campaigns.show", campaign.id))}
                    >
                        <Eye className="h-3.5 w-3.5 text-gray-500" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-7 w-7"
                        onClick={() => handleDelete(campaign)}
                    >
                        <Trash2 className="h-3.5 w-3.5 text-red-500" />
                    </Button>
                </div>
            );
        },
    },
];
