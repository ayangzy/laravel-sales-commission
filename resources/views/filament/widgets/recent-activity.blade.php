<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Recent Activity
        </x-slot>

        <div class="space-y-3">
            @forelse($this->getActivities() as $activity)
                <div class="flex items-start gap-3 py-2 {{ !$loop->last ? 'border-b dark:border-gray-700' : '' }}">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="p-2 rounded-full bg-{{ $activity['color'] }}-100 dark:bg-{{ $activity['color'] }}-900/50">
                            <x-dynamic-component 
                                :component="$activity['icon']" 
                                class="w-4 h-4 text-{{ $activity['color'] }}-600 dark:text-{{ $activity['color'] }}-400" 
                            />
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $activity['title'] }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $activity['description'] }}
                        </p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-xs text-gray-400">
                            {{ $activity['timestamp']?->diffForHumans() ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="text-center py-6">
                    <x-heroicon-o-clock class="w-8 h-8 mx-auto text-gray-400" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No recent activity</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
