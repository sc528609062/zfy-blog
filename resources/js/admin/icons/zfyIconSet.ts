export interface ZfyIconDefinition {
    viewBox: string;
    paths: string[];
}

export type ZfyIconName = keyof typeof zfyIconSet;

function icon(...paths: string[]): ZfyIconDefinition {
    return {
        viewBox: '0 0 24 24',
        paths,
    };
}

export const zfyIconSet = {
    undo: icon('M9 7H4v5', 'M4 12c2.2-3.2 5.1-4.8 8.3-4.8 4.1 0 7.2 3.1 7.2 7s-3.1 7-7.2 7c-2.4 0-4.5-1-5.8-2.7'),
    redo: icon('M15 7h5v5', 'M20 12c-2.2-3.2-5.1-4.8-8.3-4.8-4.1 0-7.2 3.1-7.2 7s3.1 7 7.2 7c2.4 0 4.5-1 5.8-2.7'),
    bold: icon('M8 5h5.2a3.2 3.2 0 0 1 0 6.4H8V5Z', 'M8 11.4h6.1a3.8 3.8 0 0 1 0 7.6H8v-7.6Z'),
    italic: icon('M10 5h8', 'M6 19h8', 'M14 5 10 19'),
    strike: icon('M5 12h14', 'M8 8c.9-2 2.8-3 5.1-3 2 0 3.5.7 4.4 1.8', 'M16.5 15.2c-.8 2.4-3 3.8-5.8 3.8-2.1 0-3.8-.7-5.1-2.1'),
    inlineCode: icon('M9 8 5 12l4 4', 'M15 8l4 4-4 4', 'M13 6l-2 12'),
    heading: icon('M5 5v14', 'M19 5v14', 'M5 12h14', 'M13 19h6'),
    quote: icon('M8 7H5.8C4.8 7 4 7.8 4 8.8V11c0 3 1.3 5.1 4 6', 'M18 7h-2.2c-1 0-1.8.8-1.8 1.8V11c0 3 1.3 5.1 4 6'),
    orderedList: icon('M10 7h10', 'M10 12h10', 'M10 17h10', 'M4 6h2v4', 'M4 15.5c0-.8.6-1.5 1.5-1.5S7 14.7 7 15.5c0 1.5-3 1.8-3 3.5h3'),
    unorderedList: icon('M9 7h11', 'M9 12h11', 'M9 17h11', 'M4.5 7h.1', 'M4.5 12h.1', 'M4.5 17h.1'),
    taskList: icon('M9 7h11', 'M9 12h11', 'M9 17h11', 'M4 7l1 1 2-2', 'M4 12l1 1 2-2', 'M4.5 17h2'),
    divider: icon('M4 12h16'),
    link: icon('M10 14a4 4 0 0 1 0-5.7l1.4-1.4a4 4 0 0 1 5.7 5.7l-.8.8', 'M14 10a4 4 0 0 1 0 5.7l-1.4 1.4a4 4 0 0 1-5.7-5.7l.8-.8'),
    image: icon('M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z', 'M8 9h.1', 'M5 17l4.5-4.5 3 3 2-2L19 18'),
    table: icon('M4 5h16v14H4V5Z', 'M4 10h16', 'M4 15h16', 'M9 5v14', 'M15 5v14'),
    codeBlock: icon('M5 5h14v14H5V5Z', 'M9 9l-2 3 2 3', 'M15 9l2 3-2 3', 'M13 8l-2 8'),
    html: icon('M8 8 4 12l4 4', 'M16 8l4 4-4 4', 'M14 5l-4 14'),
    time: icon('M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z', 'M12 7v5l3 2'),
    indent: icon('M4 7h16', 'M10 12h10', 'M4 17h16', 'M4 10l3 2-3 2'),
    symbols: icon('M7 4l1.2 3.2L11.5 7l-2.6 2.1L10 12.5 7 10.6l-3 1.9 1.1-3.4L2.5 7l3.3.2L7 4Z', 'M16 7h5', 'M18.5 4.5v5', 'M14 16h8', 'M18 12v8'),
    emoji: icon('M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z', 'M8.5 10h.1', 'M15.5 10h.1', 'M8 14c1 1.6 2.3 2.4 4 2.4s3-.8 4-2.4'),
    alert: icon('M12 4 3.5 19h17L12 4Z', 'M12 9v4', 'M12 16h.1'),
    callout: icon('M5 5h14v10H8l-3 4V5Z', 'M9 9h6', 'M9 12h4'),
    centerTitle: icon('M5 6h14', 'M8 10h8', 'M6 14h12', 'M9 18h6'),
    card: icon('M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z', 'M7 9h10', 'M7 13h7', 'M7 16h4'),
    cards: icon('M7 7h12v12H7V7Z', 'M5 5h12', 'M5 5v12'),
    describe: icon('M6 4h9l3 3v13H6V4Z', 'M15 4v4h4', 'M9 11h6', 'M9 15h6', 'M9 18h3'),
    message: icon('M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7A2.5 2.5 0 0 1 17.5 16H10l-5 4v-4.5A2.5 2.5 0 0 1 4 13.5v-7Z', 'M8 9h8', 'M8 12h5'),
    progress: icon('M5 7h14', 'M5 12h14', 'M5 17h14', 'M5 7h8', 'M5 12h5', 'M5 17h11'),
    collapse: icon('M5 6h14', 'M8 10h8', 'M5 14h14', 'M9 18l3-3 3 3'),
    tabs: icon('M4 7h6l1.5 3H20v9H4V7Z', 'M4 10h16', 'M8 14h8'),
    video: icon('M5 6.5A2.5 2.5 0 0 1 7.5 4h7A2.5 2.5 0 0 1 17 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 5 17.5v-11Z', 'M17 10l4-2.5v9L17 14v-4Z'),
    play: icon('M6 5h12v14H6V5Z', 'M10 9l5 3-5 3V9Z'),
    musicList: icon('M6 6h8', 'M6 10h8', 'M6 14h5', 'M17 5v10.5a2.5 2.5 0 1 1-1-2V7l4-1'),
    music: icon('M14 5v10.5a2.5 2.5 0 1 1-1-2V7l6-1.5V4l-5 1Z'),
    audio: icon('M12 4a3 3 0 0 0-3 3v5a3 3 0 0 0 6 0V7a3 3 0 0 0-3-3Z', 'M6 11a6 6 0 0 0 12 0', 'M12 17v4', 'M9 21h6'),
    cloud: icon('M7 18h10.5a3.5 3.5 0 0 0 .7-6.9A5.5 5.5 0 0 0 7.6 9.7 4.2 4.2 0 0 0 7 18Z', 'M12 12v6', 'M9.5 15.5 12 18l2.5-2.5'),
    button: icon('M5 8h14a4 4 0 0 1 0 8H5a4 4 0 0 1 0-8Z', 'M9 12h6'),
    magicButton: icon('M5 19 19 5', 'M14 5h5v5', 'M5 5l.5 1.5L7 7l-1.5.5L5 9l-.5-1.5L3 7l1.5-.5L5 5Z', 'M17 16l.5 1.5L19 18l-1.5.5L17 20l-.5-1.5L15 18l1.5-.5L17 16Z'),
    noteButton: icon('M6 4h12v16H6V4Z', 'M9 8h6', 'M9 12h6', 'M9 16h3', 'M18 4l-3 3'),
    dotted: icon('M5 12h.1', 'M9 12h.1', 'M13 12h.1', 'M17 12h.1', 'M21 12h.1'),
    timeline: icon('M8 5v14', 'M8 7h10', 'M8 12h7', 'M8 17h11', 'M6 7h4', 'M6 12h4', 'M6 17h4'),
    copy: icon('M8 8h11v11H8V8Z', 'M5 16V5h11'),
    lamp: icon('M8 10a4 4 0 1 1 8 0c0 1.4-.7 2.4-1.7 3.2-.8.6-1.3 1.3-1.3 2.3h-2c0-1-.5-1.7-1.3-2.3C8.7 12.4 8 11.4 8 10Z', 'M10 19h4', 'M11 22h2'),
    grid: icon('M4 4h6v6H4V4Z', 'M14 4h6v6h-6V4Z', 'M4 14h6v6H4v-6Z', 'M14 14h6v6h-6v-6Z'),
    hide: icon('M3 12s3.2-6 9-6 9 6 9 6-3.2 6-9 6-9-6-9-6Z', 'M9.8 9.8a3 3 0 0 0 4.4 4.4', 'M4 4l16 16'),
    clean: icon('M5 7h14', 'M9 7V5h6v2', 'M8 10v8', 'M12 10v8', 'M16 10v8', 'M7 7l1 14h8l1-14'),
    download: icon('M12 4v11', 'M8 11l4 4 4-4', 'M5 20h14'),
    fullscreen: icon('M4 9V4h5', 'M15 4h5v5', 'M20 15v5h-5', 'M9 20H4v-5'),
    preview: icon('M3 12s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6Z', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z'),
    save: icon('M5 4h11l3 3v13H5V4Z', 'M8 4v6h7V4', 'M8 20v-6h8v6'),
    publish: icon('M12 20V7', 'M7 12l5-5 5 5', 'M5 20h14'),
    component: icon('M8 4l4 4-4 4-4-4 4-4Z', 'M16 4l4 4-4 4-4-4 4-4Z', 'M8 12l4 4-4 4-4-4 4-4Z', 'M16 12l4 4-4 4-4-4 4-4Z'),
};
