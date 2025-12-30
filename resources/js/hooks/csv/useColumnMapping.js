
import { useCallback, useEffect, useState } from 'react';

export function useColumnMapping(initialHeaders = [], fields = []) {
    const [mapping, setMapping] = useState({});
    // mapping format: { [csvHeader]: "entityFieldKey" }

    // Auto-map columns based on similarity
    const autoMap = useCallback(() => {
        const newMapping = {};

        initialHeaders.forEach(header => {
            const normalizedHeader = header.toLowerCase().replace(/[^a-z0-9]/g, '');

            // Find best matching field
            const match = fields.find(field => {
                const normalizedFieldLabel = field.label.toLowerCase().replace(/[^a-z0-9]/g, '');
                const normalizedFieldKey = field.key.toLowerCase().replace(/[^a-z0-9]/g, '');

                return normalizedHeader === normalizedFieldLabel ||
                    normalizedHeader === normalizedFieldKey ||
                    normalizedHeader.includes(normalizedFieldLabel) ||
                    normalizedFieldLabel.includes(normalizedHeader);
            });

            if (match) {
                newMapping[header] = match.key;
            }
        });

        setMapping(newMapping);
    }, [initialHeaders, fields]);

    const updateMapping = (csvHeader, fieldKey) => {
        setMapping(prev => ({
            ...prev,
            [csvHeader]: fieldKey
        }));
    };

    const getMappedField = (csvHeader) => mapping[csvHeader];

    const isFieldMapped = (fieldKey) => {
        return Object.values(mapping).includes(fieldKey);
    };

    const getMissingRequiredFields = () => {
        return fields
            .filter(field => field.required)
            .filter(field => !isFieldMapped(field.key));
    };

    // Auto-run automap when headers change
    useEffect(() => {
        if (initialHeaders.length > 0) {
            autoMap();
        }
    }, [initialHeaders, autoMap]);

    return {
        mapping,
        updateMapping,
        getMappedField,
        isFieldMapped,
        getMissingRequiredFields,
        autoMap,
        resetMapping: () => setMapping({})
    };
}
