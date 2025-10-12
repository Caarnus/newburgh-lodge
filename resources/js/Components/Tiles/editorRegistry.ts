import EditorText from '@/Components/Tiles/Editors/EditorText.vue'
import EditorNewsletter from '@/Components/Tiles/Editors/EditorNewsletter.vue'
import EditorImage from '@/Components/Tiles/Editors/EditorImage.vue'
import EditorImageText from '@/Components/Tiles/Editors/EditorImageText.vue'
import EditorLinks from '@/Components/Tiles/Editors/EditorLinks.vue'
import EditorCTA from '@/Components/Tiles/Editors/EditorCTA.vue'
import EditorEvents from '@/Components/Tiles/Editors/EditorEvents.vue'

export const editorRegistry: Record<string, any> = {
    text: EditorText,
    newsletter: EditorNewsletter,
    image: EditorImage,
    image_text: EditorImageText,
    links: EditorLinks,
    cta: EditorCTA,
    events: EditorEvents,
}
