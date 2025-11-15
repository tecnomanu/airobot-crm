import TemplateManager from "./TemplateManager";

export default function TemplatesTab({ campaign, templates, selectedWhatsappSource }) {
    return (
        <div className="space-y-4">
            <TemplateManager 
                campaign={campaign} 
                templates={templates}
                selectedWhatsappSource={selectedWhatsappSource}
            />
        </div>
    );
}

