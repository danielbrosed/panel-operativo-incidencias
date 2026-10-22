import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input, Select } from '@/components/ui/form-controls';
import { IconClose, IconSearch } from '@/components/ui/icons';
import type { IncidentPriorityValue, PriorityOption } from '@/types/incident';

interface FilterBarProps {
    search: string;
    priority: IncidentPriorityValue | null;
    priorities: PriorityOption[];
    filtered: boolean;
    onSearchChange: (search: string) => void;
    onSearchSubmit: () => void;
    onSearchClear: () => void;
    onPriorityChange: (priority: IncidentPriorityValue | null) => void;
    onClearAll: () => void;
}

export function FilterBar({
    search,
    priority,
    priorities,
    filtered,
    onSearchChange,
    onSearchSubmit,
    onSearchClear,
    onPriorityChange,
    onClearAll,
}: FilterBarProps) {
    return (
        <div className="flex flex-col gap-3 md:flex-row md:items-end">
            <form
                role="search"
                className="flex-1"
                onSubmit={(event) => {
                    event.preventDefault();
                    onSearchSubmit();
                }}
            >
                <Field id="filter-search" label="Buscar">
                    {(control) => (
                        <div className="relative">
                            <IconSearch className="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-muted" />
                            <Input
                                {...control}
                                type="search"
                                name="q"
                                // Mirrors SEARCH_MAX_LENGTH in app/Data/IncidentFilters.php.
                                maxLength={100}
                                autoComplete="off"
                                placeholder="Texto, tienda o persona"
                                value={search}
                                onChange={(event) => onSearchChange(event.target.value)}
                                className="px-11 [&::-webkit-search-cancel-button]:appearance-none"
                            />
                            {search !== '' && (
                                <button
                                    type="button"
                                    onClick={onSearchClear}
                                    aria-label="Borrar la búsqueda"
                                    className="absolute top-1/2 right-2 grid size-7 -translate-y-1/2 place-items-center rounded-full text-muted hover:bg-surface hover:text-navy"
                                >
                                    <IconClose />
                                </button>
                            )}
                        </div>
                    )}
                </Field>
            </form>

            <Field id="filter-priority" label="Prioridad" className="md:w-56">
                {(control) => (
                    <Select
                        {...control}
                        value={priority ?? ''}
                        onChange={(event) =>
                            onPriorityChange(
                                event.target.value === '' ? null : (event.target.value as IncidentPriorityValue),
                            )
                        }
                    >
                        <option value="">Todas</option>
                        {priorities.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                )}
            </Field>

            {filtered && (
                <Button variant="link" onClick={onClearAll} className="self-start md:h-10 md:self-auto">
                    Quitar filtros
                </Button>
            )}
        </div>
    );
}
