import * as React from "react";
import {
    Dialog,
    DialogContent,
    DialogPortal,
    DialogOverlay,
} from "@/components/ui/dialog";
import { cn } from "@/lib/utils";

export default function Modal({
    children,
    show = false,
    maxWidth = "2xl",
    closeable = true,
    onClose = () => {},
}) {
    const maxWidthClass = {
        sm: "sm:max-w-sm",
        md: "sm:max-w-md",
        lg: "sm:max-w-lg",
        xl: "sm:max-w-xl",
        "2xl": "sm:max-w-2xl",
        "3xl": "sm:max-w-3xl",
        "4xl": "sm:max-w-4xl",
        "5xl": "sm:max-w-5xl",
    }[maxWidth];

    return (
        <Dialog open={show} onOpenChange={(open) => !open && closeable && onClose()}>
            <DialogPortal>
                <DialogOverlay className="bg-gray-500/75 dark:bg-gray-900/75" />
                <DialogContent
                    className={cn(
                        "gap-0 p-0 [&>button]:hidden",
                        maxWidthClass
                    )}
                    onPointerDownOutside={(e) => {
                        if (!closeable) {
                            e.preventDefault();
                        }
                    }}
                    onEscapeKeyDown={(e) => {
                        if (!closeable) {
                            e.preventDefault();
                        }
                    }}
                >
                    {children}
                </DialogContent>
            </DialogPortal>
        </Dialog>
    );
}
