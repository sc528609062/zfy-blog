import { computed, type Ref } from 'vue';

export interface EditorOutlineItem {
    id: string;
    level: number;
    line: number;
    title: string;
    index: number;
}

export function useEditorOutline(markdown: Ref<string>) {
    const outline = computed<EditorOutlineItem[]>(() => parseOutline(markdown.value));

    return { outline };
}

function parseOutline(value: string): EditorOutlineItem[] {
    const lines = String(value || '').replace(/\r\n?/g, '\n').split('\n');
    const result: EditorOutlineItem[] = [];
    let inFence = false;
    let fence = '';
    let inFrontMatter = lines[0]?.trim() === '---';

    lines.forEach((line, index) => {
        if (inFrontMatter) {
            if (index > 0 && line.trim() === '---') {
                inFrontMatter = false;
            }
            return;
        }

        const fenceMatch = line.match(/^\s*(```|~~~)/);
        if (fenceMatch) {
            if (!inFence) {
                inFence = true;
                fence = fenceMatch[1];
            } else if (fenceMatch[1] === fence) {
                inFence = false;
                fence = '';
            }
            return;
        }

        if (inFence) {
            return;
        }

        const atx = line.match(/^\s{0,3}(#{1,6})\s+(.+?)\s*#*\s*$/);
        const setext = index + 1 < lines.length ? lines[index + 1].match(/^\s{0,3}(=+|-+)\s*$/) : null;
        const title = atx?.[2] || (setext && line.trim() ? line.trim() : '');
        const level = atx ? atx[1].length : setext ? (setext[1][0] === '=' ? 1 : 2) : 0;

        if (!title || !level) {
            return;
        }

        result.push({
            id: `outline-${index + 1}`,
            level,
            line: index + 1,
            title: title.replace(/\[([^\]]+)]\([^)]*\)|[*_`~]/g, '$1').trim(),
            index: result.length,
        });
    });

    return result;
}
