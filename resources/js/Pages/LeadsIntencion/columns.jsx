import { ArrowUpDown, Eye } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Badge } from "@/components/ui/badge";
import { router } from "@inertiajs/react";

export const getLeadIntencionColumns = () => [
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
        accessorKey: "phone",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                >
                    Phone
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => <div className="font-medium">{row.getValue("phone")}</div>,
    },
    {
        accessorKey: "name",
        header: "Name",
        cell: ({ row }) => <div>{row.getValue("name") || "-"}</div>,
    },
    {
        accessorKey: "last_message",
        header: "Last Message",
        cell: ({ row }) => (
            <div className="max-w-xs truncate" title={row.getValue("last_message")}>
                {row.getValue("last_message") || "-"}
            </div>
        ),
    },
    {
        accessorKey: "intention",
        header: "Intention",
        cell: ({ row }) => {
            const intention = row.getValue("intention");
            const colors = {
                interested: "bg-green-100 text-green-800 hover:bg-green-100",
                not_interested: "bg-red-100 text-red-800 hover:bg-red-100",
            };
            
            if (intention === "interested" || intention === "not_interested") {
                return (
                    <Badge className={colors[intention]}>
                        {intention === "interested" ? "Interested" : "Not Interested"}
                    </Badge>
                );
            }
            
            return (
                <div className="max-w-xs truncate text-muted-foreground" title={intention}>
                    {intention || "-"}
                </div>
            );
        },
    },
    {
        accessorKey: "source",
        header: "Source",
        cell: ({ row }) => (
            <Badge variant="outline">{row.original.source_label}</Badge>
        ),
    },
    {
        accessorKey: "campaign",
        header: "Campaign",
        cell: ({ row }) => <div>{row.original.campaign?.name || "-"}</div>,
        enableSorting: false,
    },
    {
        accessorKey: "status",
        header: "Status",
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
                <Badge className={colors[status] || "bg-gray-100 text-gray-800"}>
                    {row.original.status_label}
                </Badge>
            );
        },
    },
    {
        accessorKey: "created_at",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                >
                    Date
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => (
            <div>
                {new Date(row.getValue("created_at")).toLocaleDateString("en-US")}
            </div>
        ),
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const lead = row.original;
            return (
                <div className="flex justify-end gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => router.visit(route("leads-intencion.show", lead.id))}
                        title="View intention details"
                    >
                        <Eye className="h-4 w-4" />
                    </Button>
                </div>
            );
        },
    },
];

