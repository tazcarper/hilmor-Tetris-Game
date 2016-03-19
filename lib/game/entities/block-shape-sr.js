ig.module( 
	'game.entities.block-shape-sr'
)
.requires(
	'game.entities.block',
    'game.entities.block-container'
)
.defines(function() {

    EntityBlockShapeSR = EntityBlockContainer.extend({
        // hilmor 'r'
        color: EntityBlock.color.COLOR_SR,    
        
        // rotationShapes: [
        //     [[0,1,1],
        //      [0,1,0],
        //      [0,0,0]],

        //     [[0,0,0],
        //      [0,1,1],
        //      [0,0,1]],

        //     [[0,0,0],
        //      [0,1,0],
        //      [1,1,0]],

        //     [[1,0,0],
        //      [1,1,0],
        //      [0,0,0]]
        // ]
        rotationShapes: [
            [[0,1,1],
             [0,1,0],
             [0,1,0]],
            [[0,0,0],
             [1,1,1],
             [0,0,1]],

            [[0,1,0],
             [0,1,0],
             [1,1,0]],

            [[1,0,0],
             [1,1,1],
             [0,0,0]]
        ]
    });
});