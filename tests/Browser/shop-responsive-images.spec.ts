import { expect, test } from '@playwright/test';

test('shop product images render completely without container cropping', async ({
    page,
}) => {
    await page.goto('/shop');

    const cards = page.locator('[data-shop-product-card]');

    await expect(cards.first()).toBeVisible();

    const cardCount = Math.min(await cards.count(), 4);

    let checkedImages = 0;

    for (let index = 0; index < cardCount; index++) {
        const image = cards
            .nth(index)
            .locator('[data-shop-product-image]');

        if ((await image.count()) === 0) {
            continue;
        }

        await expect(image).toBeVisible();

        await expect
            .poll(() =>
                image.evaluate((element) => {
                    const imageElement = element as HTMLImageElement;

                    return (
                        imageElement.complete &&
                        imageElement.naturalWidth > 0 &&
                        imageElement.naturalHeight > 0
                    );
                }),
            )
            .toBe(true);

        const metrics = await image.evaluate((element) => {
            const imageElement = element as HTMLImageElement;
            const imageRect = imageElement.getBoundingClientRect();
            const frameRect =
                imageElement.parentElement?.getBoundingClientRect();

            return {
                naturalRatio:
                    imageElement.naturalWidth / imageElement.naturalHeight,
                renderedRatio: imageRect.width / imageRect.height,
                imageWidth: imageRect.width,
                imageHeight: imageRect.height,
                frameWidth: frameRect?.width ?? 0,
                frameHeight: frameRect?.height ?? 0,
                objectFit: window.getComputedStyle(imageElement).objectFit,
            };
        });

        expect(
            Math.abs(metrics.naturalRatio - metrics.renderedRatio),
        ).toBeLessThan(0.02);

        expect(Math.abs(metrics.frameWidth - metrics.imageWidth)).toBeLessThan(
            1,
        );

        expect(Math.abs(metrics.frameHeight - metrics.imageHeight)).toBeLessThan(
            1,
        );

        expect(metrics.objectFit).not.toBe('cover');

        checkedImages++;
    }

    expect(checkedImages).toBeGreaterThan(0);
});
