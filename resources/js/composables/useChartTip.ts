// resources/js/composables/useChartTip.ts
//
// The hover tooltip state for SVG charts. Shared by DashboardCharts and
// MyBarChartConstructor, and rendered by MyChartTooltip. Hover only ever
// repeats a value the chart or a table already shows; it never gates one.

import { ref } from 'vue'

export interface ChartTip {
    x: number
    y: number
    label: string
    value: string
}

export function useChartTip() {
    const tip = ref<ChartTip | null>(null)

    /**
     * Place the tooltip at the pointer, measured from `host` (the
     * positioned element the tooltip renders inside). Without a host it
     * measures from the chart's own container, which is what
     * DashboardCharts has always done.
     */
    function showTip(event: MouseEvent, label: string, value: string, host?: HTMLElement | null) {
        const box = (host ?? (event.currentTarget as SVGElement).ownerSVGElement?.parentElement)?.getBoundingClientRect()
        if (!box) return
        tip.value = { x: event.clientX - box.left, y: event.clientY - box.top, label, value }
    }

    function hideTip() {
        tip.value = null
    }

    return { tip, showTip, hideTip }
}
