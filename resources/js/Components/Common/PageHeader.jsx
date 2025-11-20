import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";

export default function PageHeader({
    title,
    subtitle,
    backButton,
    actions,
    badges,
    className,
    compact = false,
}) {
    return (
        <div
            className={cn(
                "flex items-center justify-between w-full",
                className
            )}
        >
            <div className="flex items-center gap-3 min-w-0 flex-1">
                {backButton &&
                    (backButton.href ? (
                        <Link href={backButton.href}>
                            <Button
                                variant={backButton.variant || "ghost"}
                                size="icon"
                                className="h-8 w-8"
                            >
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                    ) : (
                        <Button
                            variant={backButton.variant || "ghost"}
                            size="icon"
                            className="h-8 w-8"
                            onClick={backButton.onClick}
                        >
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                    ))}
                <div className="min-w-0 flex-1">
                    {compact ? (
                        <div className="flex items-center gap-2 min-w-0">
                            <h1 className="text-base font-semibold tracking-tight truncate">
                                {title}
                            </h1>
                            {badges && (
                                <div className="flex items-center gap-1.5 flex-shrink-0">
                                    {badges.map((badge, index) => (
                                        <span
                                            key={index}
                                            className={cn(
                                                "text-xs px-1.5 py-0.5 rounded font-medium whitespace-nowrap",
                                                badge.className ||
                                                    "bg-muted text-muted-foreground"
                                            )}
                                        >
                                            {badge.label}
                                        </span>
                                    ))}
                                </div>
                            )}
                            {subtitle && (
                                <span className="text-xs text-muted-foreground truncate ml-2">
                                    {typeof subtitle === "string"
                                        ? subtitle
                                        : subtitle}
                                </span>
                            )}
                        </div>
                    ) : (
                        <>
                            <div className="flex items-center gap-2 flex-wrap">
                                <h1 className="text-3xl font-bold tracking-tight truncate">
                                    {title}
                                </h1>
                                {badges && (
                                    <div className="flex items-center gap-2 flex-wrap">
                                        {badges.map((badge, index) => (
                                            <span
                                                key={index}
                                                className={cn(
                                                    "text-xs px-2 py-1 rounded-md font-medium",
                                                    badge.className ||
                                                        "bg-muted text-muted-foreground"
                                                )}
                                            >
                                                {badge.label}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>
                            {subtitle && (
                                <div className="text-muted-foreground mt-1">
                                    {typeof subtitle === "string" ? (
                                        <p className="text-sm">{subtitle}</p>
                                    ) : (
                                        subtitle
                                    )}
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
            {actions && (
                <div className="flex items-center gap-2 flex-shrink-0 ml-4">
                    {Array.isArray(actions) ? (
                        actions.map((action, index) => (
                            <div key={index}>{action}</div>
                        ))
                    ) : (
                        <div>{actions}</div>
                    )}
                </div>
            )}
        </div>
    );
}
