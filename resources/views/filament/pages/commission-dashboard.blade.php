<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        @foreach($this->getStats() as $stat)
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900">
                        <x-dynamic-component :component="$stat['icon']"
                            class="w-6 h-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="text-2xl font-bold">{{ $stat['value'] }}</p>
                        <p class="text-xs text-gray-400">{{ $stat['description'] }}</p>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-filament::section>
            <x-slot name="heading">
                Recent Earnings
            </x-slot>

            <div class="space-y-2">
                @forelse(\SalesCommission\Models\CommissionEarning::latest('earned_at')->take(5)->get() as $earning)
                    <div class="flex justify-between items-center py-2 border-b dark:border-gray-700">
                        <div>
                            <span class="text-sm font-medium">Agent #{{ $earning->agent_id }}</span>
                            <span class="text-xs text-gray-500 ml-2">{{ $earning->earned_at?->diffForHumans() }}</span>
                        </div>
                        <div class="text-right">
                            <span
                                class="font-bold text-green-600">${{ number_format($earning->commission_amount, 2) }}</span>
                            <span class="text-xs text-gray-500 block">from
                                ${{ number_format($earning->base_amount, 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No recent earnings</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Pending Payouts
            </x-slot>

            <div class="space-y-2">
                @forelse(\SalesCommission\Models\Payout::whereIn('status', ['pending_approval', 'approved'])->latest()->take(5)->get() as $payout)
                    <div class="flex justify-between items-center py-2 border-b dark:border-gray-700">
                        <div>
                            <span class="text-sm font-medium">{{ $payout->period }}</span>
                            <span
                                class="px-2 py-1 text-xs rounded-full {{ $payout->status === 'approved' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $payout->status)) }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="font-bold">${{ number_format($payout->total_amount, 2) }}</span>
                            <span class="text-xs text-gray-500 block">{{ $payout->total_earnings_count }} earnings</span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No pending payouts</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>