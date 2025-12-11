const WIDTH = 1000;
const HEIGHT = 600;
const RADIUS = 20;

// Initialize SVG container
const svgContainer = d3.select("#tree-container")
    .append("svg")
    .attr("width", WIDTH)
    .attr("height", HEIGHT);

const svg = svgContainer.append("g")
    .attr("transform", "translate(50, 50)"); // Shift visualization

const treeLayout = d3.tree().size([WIDTH - 100, HEIGHT - 100]);

// Function to send command to PHP server
function send_command(command) {
    let value = '';
    
    if (command === 'insert') {
        value = document.getElementById('insertValue').value;
    } else if (command === 'delete') {
        value = document.getElementById('deleteValue').value;
    }
    
    // Check if a value is provided for insert/delete
    if ((command === 'insert' || command === 'delete') && !value) {
        // Use a simple alert since custom modal implementation is complex
        alert("Please enter a value to " + command + ".");
        return;
    }
    
    // Clear input fields after command execution
    document.getElementById('insertValue').value = '';
    document.getElementById('deleteValue').value = '';

    // Use the native Fetch API to communicate with PHP
    fetch('rbt_logic.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        // Send command and value data
        body: `command=${command}&value=${value}&reset=${command === 'reset' ? 1 : 0}`
    })
    .then(response => response.json())
    .then(data => {
        // 1. Update Stats
        document.getElementById('stat_nodes').textContent = data.stats.nodes;
        document.getElementById('stat_rotations').textContent = data.stats.rotations;
        document.getElementById('stat_height').textContent = data.stats.height;

        // 2. Visualize Tree
        update_visualization(data.tree);
    })
    .catch(error => console.error('Error fetching tree data:', error));
}

// Function to render the tree using D3.js
function update_visualization(treeData) {
    // CRITICAL FIX: Clear the previous drawing completely
    svg.selectAll('*').remove();

    if (!treeData) {
        svg.append("text")
           .attr("x", (WIDTH - 100) / 2)
           .attr("y", (HEIGHT - 100) / 2)
           .text("Tree is Empty")
           .attr("text-anchor", "middle");
        return;
    }

    // Convert the PHP array structure into D3 hierarchy format
    const root = d3.hierarchy(treeData);
    const nodes = treeLayout(root).descendants();
    const links = root.links();

    // Draw Links (The lines connecting nodes)
    svg.selectAll(".link")
        .data(links)
        .enter().append("path")
        .attr("class", "link")
        .attr("d", d3.linkVertical()
            .x(d => d.x)
            .y(d => d.y)
        );

    // Draw Nodes (The circles and text)
    const node = svg.selectAll(".node")
        .data(nodes)
        .enter().append("g")
        .attr("class", d => `node ${d.data.color}-node`) // Use color for CSS class
        .attr("transform", d => `translate(${d.x}, ${d.y})`);

    // Draw the circle
    node.append("circle")
        .attr("r", RADIUS);

    // Draw the key text
    node.append("text")
        .attr("dy", "0.31em")
        .attr("text-anchor", "middle")
        .text(d => d.data.key)
        .style("fill", d => (d.data.color === 'black') ? 'white' : 'black');
}

// Initialize the visualizer when the page loads
document.addEventListener('DOMContentLoaded', () => {
    send_command('get'); 
});