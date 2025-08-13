import TileCTA from '@/Components/Tiles/TileCTA.vue'
import TileEvents from '@/Components/Tiles/TileEvents.vue'
import TileImage from '@/Components/Tiles/TileImage.vue'
import TileImageText from '@/Components/Tiles/TileImageText.vue'
import TileLinks from '@/Components/Tiles/TileLinks.vue'
import TileNewsletter from '@/Components/Tiles/TileNewsletter.vue'
import TileText from '@/Components/Tiles/TileText.vue'

export const tileRegistry: Record<string, any> = {
    cta: TileCTA,
    events: TileEvents,
    image: TileImage,
    image_text: TileImageText,
    links: TileLinks,
    newsletter: TileNewsletter,
    text: TileText,
}
