import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input, Select } from '@/components/ui/form-controls';
import { IconClose, IconSearch } from '@/components/ui/icons';
import type { IncidentFiltersState } from '@/hooks/use-incident-filters';
import type { IncidentPriorityValue, PriorityOption } from '@/types/incident';

interface FilterBarProps {
    state: IncidentFiltersState;
    priorities: PriorityOption[];
}

export function FilterBar({ state, priorities }: FilterBarProps) {
    return (
        <div className="flex flex-col gap-3 md:flex-row md:items-end">
            <form
                role="search"
                className="flex-1"
                onSubmit={(event) => {
                    event.preventDefault();
                    state.submitSearch();
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
                                maxLength={100}
                                autoComplete="off"
                                placeholder="Título, tienda, solicitante o responsable"
                                value={state.search}
                                onChange={(event) => state.changeSearch(event.target.value)}
                                className="pr-11 pl-11 [&::-webkit-search-cancel-button]:appearance-none"
                            />
                            {state.search !== '' && (
                                <button
                                    type="button"
                                    onClick={state.clearSearch}
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
                        value={state.filters.priority ?? ''}
                        onChange={(event) =>
                            state.setPriority(
                                event.target.value === '' ? null : (event.target.value as IncidentPriorityValue),
                            )
                        }
                    >
                        <option value="">Todas</option>
                        {priorities.map((priority) => (
                            <option key={priority.value} value={priority.value}>
                                {priority.label}
                            </option>
                        ))}
                    </Select>
                )}
            </Field>

            {state.isFiltered && (
                <Button variant="link" onClick={state.clearAll} className="self-start md:h-10 md:self-auto">
                    Quitar filtros
                </Button>
            )}
        </div>
    );
}
