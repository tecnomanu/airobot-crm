import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router } from "@inertiajs/react";
import { Search, X } from "lucide-react";
import { useState } from "react";
import { getLeadIntencionColumns } from "./columns";

export default function LeadsIntencionIndex({ leads, campaigns, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || "");

    const handleFilterChange = (name, value) => {
        router.get(
            route("leads-intencion.index"),
            { ...filters, [name]: value },
            { preserveState: true }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("leads-intencion.index"),
            { ...filters, search: searchTerm },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        setSearchTerm("");
        router.get(route("leads-intencion.index"), {}, { preserveState: true });
    };

    return (
        <AppLayout>
            <Head title="Leads with Intention" />

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Leads with Intention
                    </h1>
                    <p className="text-muted-foreground">
                        Leads captured through WhatsApp and AI Agent with
                        registered intention
                    </p>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="grid gap-4 md:grid-cols-4">
                            <form
                                onSubmit={handleSearch}
                                className="flex gap-2"
                            >
                                <Input
                                    placeholder="Search by phone or name..."
                                    value={searchTerm}
                                    onChange={(e) =>
                                        setSearchTerm(e.target.value)
                                    }
                                />
                                <Button
                                    type="submit"
                                    size="icon"
                                    variant="outline"
                                >
                                    <Search className="h-4 w-4" />
                                </Button>
                            </form>

                            <Select
                                value={filters.campaign_id || "all"}
                                onValueChange={(value) =>
                                    handleFilterChange(
                                        "campaign_id",
                                        value === "all" ? "" : value
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All campaigns" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All campaigns
                                    </SelectItem>
                                    {campaigns.map((c) => (
                                        <SelectItem
                                            key={c.id}
                                            value={c.id.toString()}
                                        >
                                            {c.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={filters.status || "all"}
                                onValueChange={(value) =>
                                    handleFilterChange(
                                        "status",
                                        value === "all" ? "" : value
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All statuses
                                    </SelectItem>
                                    <SelectItem value="pending">
                                        Pending
                                    </SelectItem>
                                    <SelectItem value="in_progress">
                                        In Progress
                                    </SelectItem>
                                    <SelectItem value="contacted">
                                        Contacted
                                    </SelectItem>
                                    <SelectItem value="closed">
                                        Closed
                                    </SelectItem>
                                    <SelectItem value="invalid">
                                        Invalid
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <Button
                                variant="outline"
                                onClick={handleClearFilters}
                                className="w-full"
                            >
                                <X className="mr-2 h-4 w-4" />
                                Clear
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <DataTable
                    columns={getLeadIntencionColumns()}
                    data={leads.data}
                    filterColumn="phone"
                />
            </div>
        </AppLayout>
    );
}
